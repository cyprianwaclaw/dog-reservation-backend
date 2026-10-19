<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $start = $request->query('start');
        $end   = $request->query('end');

        $query = Visit::where('user_id', $user->id)
            ->with(['client.dogs.breed', 'dog.breed', 'service']);

        if ($start && $end) {
            $query->whereBetween('start_time', ["{$start} 00:00:00", "{$end} 23:59:59"]);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, Visit $visit)
    {
        if ($visit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        // Ładujemy klienta wraz ze WSZYSTKIMI jego psami i ich rasami
        $visit->load(['client.dogs.breed', 'dog.breed', 'service']);

        // Pobieramy historię wizyt psa
        $history = Visit::where('dog_id', $visit->dog_id)
            ->where('id', '!=', $visit->id)
            ->with('service')
            ->orderBy('start_time', 'desc')
            ->paginate(5);

        return response()->json([
            'visit'   => $visit,
            'history' => $history,
        ]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'client_name'  => 'required|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:255',
            'dog_id'       => 'nullable|exists:dogs,id',
            'dog_name'     => 'required|string|max:255',
            'breed_id'     => 'nullable|exists:breeds,id',
            'dog_breed'    => 'nullable|string|max:255',
            'dog_age'      => 'nullable|numeric|min:0',
            'dog_weight'   => 'nullable|numeric|min:0',
            'service_id'   => 'required|exists:services,id',
            'start_time'   => 'required|date_format:Y-m-d H:i:s',
            'end_time'     => 'required|date_format:Y-m-d H:i:s|after:start_time',
            'price'        => 'required|integer|min:0',
            'description'  => 'nullable|string',
        ]);

        $user = $request->user();

        // 1. Obsługa rasy psa
        $breedId = $fields['breed_id'] ?? null;
        if (!$breedId && !empty($fields['dog_breed'])) {
            $breed = Breed::firstOrCreate(
                ['name' => trim($fields['dog_breed'])],
                ['user_id' => $user->id]
            );
            $breedId = $breed->id;
        }

        // 2. Obsługa Klienta (pobranie istniejącego lub dodanie nowego)
        $client = $user->clients()->firstOrCreate(
            ['name' => trim($fields['client_name'])],
            [
                'phone' => $fields['client_phone'] ?? null,
                'email' => $fields['client_email'] ?? null,
            ]
        );

        // 3. Obsługa Psa (wybór z listy po dog_id lub utworzenie NOWEGO psa)
        $dog = null;
        if (!empty($fields['dog_id'])) {
            $dog = $client->dogs()->find($fields['dog_id']);
        }

        if (!$dog) {
            // Tworzymy nowego psa pod tym klientem
            $dog = $client->dogs()->firstOrCreate(
                ['name' => trim($fields['dog_name'])],
                [
                    'breed_id' => $breedId,
                    'age'      => $fields['dog_age'] ?? null,
                    'weight'   => $fields['dog_weight'] ?? null,
                ]
            );
        } else {
            // Aktualizacja istniejącego psa, jeśli podano zmienione parametry
            $dogUpdates = [];
            if (array_key_exists('dog_name', $fields))   $dogUpdates['name']     = $fields['dog_name'];
            if ($breedId)                                $dogUpdates['breed_id'] = $breedId;
            if (array_key_exists('dog_age', $fields))    $dogUpdates['age']      = $fields['dog_age'];
            if (array_key_exists('dog_weight', $fields)) $dogUpdates['weight']   = $fields['dog_weight'];

            if (!empty($dogUpdates)) {
                $dog->update($dogUpdates);
            }
        }

        // 4. Utworzenie nowej wizyty
        $visit = Visit::create([
            'user_id'     => $user->id,
            'client_id'   => $client->id,
            'dog_id'      => $dog->id,
            'service_id'  => $fields['service_id'],
            'start_time'  => $fields['start_time'],
            'end_time'    => $fields['end_time'],
            'price'       => $fields['price'],
            'description' => $fields['description'] ?? null,
            'status'      => 'scheduled',
        ]);

        $this->clearUserVisitsCache($user->id);

        return response()->json($visit->load(['client.dogs.breed', 'dog.breed', 'service']), 201);
    }

    public function update(Request $request, Visit $visit)
    {
        if ($visit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $fields = $request->validate([
            'service_id'   => 'sometimes|exists:services,id',
            'start_time'   => 'sometimes|date_format:Y-m-d H:i:s',
            'end_time'     => 'sometimes|date_format:Y-m-d H:i:s|after:start_time',
            'price'        => 'sometimes|integer|min:0',
            'description'  => 'nullable|string',
            'status'       => 'sometimes|in:scheduled,completed,cancelled',

            'dog_id'       => 'nullable|exists:dogs,id',
            'dog_name'     => 'sometimes|string|max:255',
            'breed_id'     => 'nullable|exists:breeds,id',
            'dog_breed'    => 'nullable|string|max:255',
            'dog_age'      => 'nullable|numeric|min:0',
            'dog_weight'   => 'nullable|numeric|min:0',

            'client_name'  => 'sometimes|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:255',
        ]);

        // 1. Aktualizacja pól samej wizyty
        $visitFields = array_intersect_key($fields, array_flip([
            'service_id', 'start_time', 'end_time', 'price', 'description', 'status',
        ]));

        if (!empty($visitFields)) {
            $visit->update($visitFields);
        }

        // 2. Obsługa rasy
        $breedId = $fields['breed_id'] ?? null;
        if (!$breedId && !empty($fields['dog_breed'])) {
            $breed = Breed::firstOrCreate(
                ['name' => trim($fields['dog_breed'])],
                ['user_id' => $request->user()->id]
            );
            $breedId = $breed->id;
        }

        // 3. Aktualizacja lub dodanie psa (BEZ NADPISYWANIA ISTNIEJĄCYCH REKORDÓW)
        if (!empty($fields['dog_name']) && $visit->client) {
            $targetDog = null;

            if (!empty($fields['dog_id'])) {
                $targetDog = $visit->client->dogs()->find($fields['dog_id']);
            }

            if (!$targetDog) {
                $targetDog = $visit->client->dogs()->firstOrCreate(
                    ['name' => trim($fields['dog_name'])],
                    [
                        'breed_id' => $breedId,
                        'age'      => $fields['dog_age'] ?? null,
                        'weight'   => $fields['dog_weight'] ?? null,
                    ]
                );
            } else {
                $dogUpdates = [];
                if (array_key_exists('dog_name', $fields))   $dogUpdates['name']     = $fields['dog_name'];
                if ($breedId)                                $dogUpdates['breed_id'] = $breedId;
                if (array_key_exists('dog_age', $fields))    $dogUpdates['age']      = $fields['dog_age'];
                if (array_key_exists('dog_weight', $fields)) $dogUpdates['weight']   = $fields['dog_weight'];

                $targetDog->update($dogUpdates);
            }

            if ($visit->dog_id !== $targetDog->id) {
                $visit->dog_id = $targetDog->id;
                $visit->save();
            }
        }

        // 4. Aktualizacja klienta
        $clientFields = [];
        if (array_key_exists('client_name', $fields))  $clientFields['name']  = $fields['client_name'];
        if (array_key_exists('client_phone', $fields)) $clientFields['phone'] = $fields['client_phone'];
        if (array_key_exists('client_email', $fields)) $clientFields['email'] = $fields['client_email'];

        if (!empty($clientFields) && $visit->client) {
            $visit->client()->update($clientFields);
        }

        // 5. Czyszczenie pamięci podręcznej
        $this->clearUserVisitsCache($request->user()->id);
        Cache::forget("visit:{$visit->id}");
        if ($visit->dog_id) {
            Cache::forget("dog:{$visit->dog_id}:history");
        }

        return response()->json($visit->fresh(['client.dogs.breed', 'dog.breed', 'service']));
    }

    public function destroy(Request $request, Visit $visit)
    {
        if ($visit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $visit->delete();
        $this->clearUserVisitsCache($request->user()->id);

        return response()->json(['message' => 'Wizyta usunięta']);
    }

    private function clearUserVisitsCache($userId)
    {
        Cache::forget("user:{$userId}:visits");
    }
}
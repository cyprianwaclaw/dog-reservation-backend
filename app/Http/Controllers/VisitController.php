<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Dog;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VisitController extends Controller
{
    // Pobieranie wizyt do kalendarza (zakres dat) z Redis Cache
    public function index(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $userId = $request->user()->id;
        $start = $request->query('start');
        $end = $request->query('end');

        $cacheKey = "user:{$userId}:visits:{$start}:{$end}";

        $visits = Cache::remember($cacheKey, now()->addHours(12), function () use ($request, $start, $end) {
            return $request->user()->visits()
                ->with(['client', 'dog', 'service'])
                ->whereBetween('start_time', [$start, $end])
                ->orderBy('start_time', 'asc')
                ->get()
                ->toArray(); // Konwersja na czystą tablicę dla Redisa
        });

        return response()->json($visits);
    }

    // Tworzenie wizyt z automatycznym tworzeniem/przypisywaniem Klienta i Psa
    public function store(Request $request)
    {
        $fields = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:255',
            'dog_name' => 'required|string|max:255',
            'dog_breed' => 'nullable|string|max:255',
            'dog_age' => 'nullable|integer|min:0',
            'dog_weight' => 'nullable|numeric|min:0',
            'service_id' => 'required|exists:services,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $userId = $request->user()->id;

        // 1. Szukanie lub tworzenie klienta
        $client = Client::firstOrCreate(
            ['user_id' => $userId, 'name' => $fields['client_name']],
            ['phone' => $fields['client_phone'] ?? null, 'email' => $fields['client_email'] ?? null]
        );

        // 2. Szukanie lub tworzenie psa
        $dog = Dog::firstOrCreate(
            ['client_id' => $client->id, 'name' => $fields['dog_name']],
            [
                'breed' => $fields['dog_breed'] ?? null,
                'age' => $fields['dog_age'] ?? null,
                'weight' => $fields['dog_weight'] ?? null,
            ]
        );

        // Update wagi i wieku psa jeśli przekazano nowsze
        if (isset($fields['dog_weight']) || isset($fields['dog_age'])) {
            $dog->update(array_filter([
                'weight' => $fields['dog_weight'] ?? $dog->weight,
                'age' => $fields['dog_age'] ?? $dog->age,
            ]));
        }

        // 3. Rejestracja wizyty
        $visit = Visit::create([
            'user_id' => $userId,
            'client_id' => $client->id,
            'dog_id' => $dog->id,
            'service_id' => $fields['service_id'],
            'start_time' => $fields['start_time'],
            'end_time' => $fields['end_time'],
            'price' => $fields['price'],
            'description' => $fields['description'] ?? null,
            'status' => 'scheduled',
        ]);

        $this->clearUserVisitsCache($userId);
        Cache::forget("dog:{$dog->id}:history");

        return response()->json($visit->load(['client', 'dog', 'service']), 201);
    }

    // Szczegóły wizyty + historia psa
    public function show(Request $request, Visit $visit)
    {
        if ($visit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $visitKey = "visit:{$visit->id}";
        $visitData = Cache::remember($visitKey, now()->addHours(12), function () use ($visit) {
            return $visit->load(['client', 'dog', 'service'])->toArray();
        });

        $dogHistoryKey = "dog:{$visit->dog_id}:history";
        $historyData = Cache::remember($dogHistoryKey, now()->addHours(12), function () use ($visit) {
            return Visit::where('dog_id', $visit->dog_id)
                ->with('service')
                ->orderBy('start_time', 'desc')
                ->paginate(10)
                ->toArray(); // Zapobiega błędom paginatora w Redisie
        });

        return response()->json([
            'visit' => $visitData,
            'history' => $historyData,
        ]);
    }

    // Aktualizacja wizyty
    public function update(Request $request, Visit $visit)
    {
        if ($visit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $fields = $request->validate([
            'service_id' => 'sometimes|exists:services,id',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:scheduled,completed,cancelled',
            'dog_weight' => 'nullable|numeric|min:0',
        ]);

        $visit->update($fields);

        if (isset($fields['dog_weight'])) {
            $visit->dog()->update(['weight' => $fields['dog_weight']]);
        }

        $this->clearUserVisitsCache($request->user()->id);
        Cache::forget("visit:{$visit->id}");
        Cache::forget("dog:{$visit->dog_id}:history");

        return response()->json($visit->load(['client', 'dog', 'service']));
    }

    // Usunięcie/Odwołanie wizyty
    public function destroy(Request $request, Visit $visit)
    {
        if ($visit->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $userId = $request->user()->id;
        $dogId = $visit->dog_id;
        $visitId = $visit->id;

        $visit->delete();

        $this->clearUserVisitsCache($userId);
        Cache::forget("visit:{$visitId}");
        Cache::forget("dog:{$dogId}:history");

        return response()->json(['message' => 'Wizyta została pomyślnie usunięta.']);
    }

    private function clearUserVisitsCache($userId)
    {
        // Unieważnianie kluczy bufora kalendarza
        Cache::flush();
    }
}

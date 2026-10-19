<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BreedController extends Controller
{
    // Pobieranie / Wyszukiwanie ras
    public function index(Request $request)
    {
        $query = $request->query('query');
        $userId = $request->user()?->id;

        // Jeśli wyszukujemy po frazie
        if ($query) {
            $breeds = Breed::where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            })
            ->where('name', 'LIKE', "%{$query}%")
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get();

            return response()->json($breeds);
        }

        // Cache pełnej listy ras na 24h
        $cacheKey = "user:{$userId}:breeds:all";
        $breeds = Cache::remember($cacheKey, now()->addHours(24), function () use ($userId) {
            return Breed::where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            })
            ->orderBy('name', 'asc')
            ->get()
            ->toArray();
        });

        return response()->json($breeds);
    }

    // Dodanie nowej rasy
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255|unique:breeds,name',
        ]);

        $breed = Breed::create([
            'user_id' => $request->user()->id,
            'name' => trim($fields['name']),
        ]);

        Cache::forget("user:{$request->user()->id}:breeds:all");

        return response()->json($breed, 201);
    }

    // Usunięcie rasy
    public function destroy(Request $request, Breed $breed)
    {
        if ($breed->user_id && $breed->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $breed->delete();
        Cache::forget("user:{$request->user()->id}:breeds:all");

        return response()->json(['message' => 'Rasa została usunięta.']);
    }
}
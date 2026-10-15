<?php

namespace App\Http\Controllers;

// use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientController extends Controller
{
    // Autouzupełnianie / Wyszukiwanie klientów z zachowaniem czystych tablic w Redis
    public function index(Request $request)
    {
        $query = $request->query('query');
        $userId = $request->user()->id;

        if (!$query) {
            return response()->json([]);
        }

        $cacheKey = "user:{$userId}:clients_search:" . md5($query);

        $clients = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $query) {
            return $request->user()->clients()
                ->with('dogs')
                ->where('name', 'LIKE', "%{$query}%")
                ->orWhere('phone', 'LIKE', "%{$query}%")
                ->limit(10)
                ->get()
                ->toArray(); // Bezpośrednia konwersja na czystą tablicę
        });

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $client = $request->user()->clients()->create($fields);

        return response()->json($client, 201);
    }
}

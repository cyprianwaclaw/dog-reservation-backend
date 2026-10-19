<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientController extends Controller
{
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
                ->with('dogs.breed')
                ->where('name', 'LIKE', "%{$query}%")
                ->orWhere('phone', 'LIKE', "%{$query}%")
                ->limit(10)
                ->get()
                ->toArray();
        });

        return response()->json($clients);
    }

    public function show(Request $request, Client $client)
    {
        if ($client->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        return response()->json($client->load('dogs.breed'));
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $client = $request->user()->clients()->create($fields);

        return response()->json($client, 201);
    }
}
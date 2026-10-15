<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    // Pobieranie usług usera z cache
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $cacheKey = "user:{$userId}:services";

        $services = Cache::remember($cacheKey, now()->addHours(24), function () use ($request) {
            return $request->user()->services()
                ->orderBy('name', 'asc')
                ->get()
                ->toArray(); // Zapis czystych danych JSON do Redisa
        });

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'default_price' => 'nullable|numeric|min:0',
        ]);

        $service = $request->user()->services()->create($fields);
        Cache::forget("user:{$request->user()->id}:services");

        return response()->json($service, 201);
    }

    public function update(Request $request, Service $service)
    {
        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $fields = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'default_price' => 'nullable|numeric|min:0',
        ]);

        $service->update($fields);
        Cache::forget("user:{$request->user()->id}:services");

        return response()->json($service);
    }

    public function destroy(Request $request, Service $service)
    {
        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Brak dostępu'], 403);
        }

        $userId = $request->user()->id;
        $service->delete();
        Cache::forget("user:{$userId}:services");

        return response()->json(['message' => 'Usługa została usunięta.']);
    }
}
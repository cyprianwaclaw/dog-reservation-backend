<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Zmiana adresu e-mail
    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $fields = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->email = $fields['email'];
        $user->save();

        return response()->json([
            'message' => 'Adres e-mail został zaktualizowany',
            'user' => $user,
        ]);
    }

    // Zmiana hasła
    public function updatePassword(Request $request)
    {
        $fields = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($fields['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Aktualne hasło jest nieprawidłowe.',
            ], 422);
        }

        $user->password = Hash::make($fields['password']);
        $user->save();

        return response()->json([
            'message' => 'Hasło zostało zmienione pomyślnie',
        ]);
    }
}
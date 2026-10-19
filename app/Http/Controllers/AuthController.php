<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Rejestracja
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $fields['name'],
            'email'    => $fields['email'],
            'password' => Hash::make($fields['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Użytkownik zarejestrowany pomyślnie',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    // Logowanie
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Uzupełnij adres e-mail',
            'email.email'       => 'Błędny adres e-mail',
            'password.required' => 'Pole hasło jest wymagane',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (! $user || ! Hash::check($fields['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Błędne dane logowania'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Zalogowano pomyślnie',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    // Zmiana / Reset Hasła (Dla zalogowanego użytkownika)
    public function updatePassword(Request $request)
    {
        $fields = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Podaj aktualne hasło',
            'password.required'         => 'Podaj nowe hasło',
            'password.min'              => 'Nowe hasło musi mieć co najmniej 8 znaków',
            'password.confirmed'        => 'Hasła nie są identyczne',
        ]);

        $user = $request->user();

        if (! Hash::check($fields['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Aktualne hasło jest nieprawidłowe'],
            ]);
        }

        $user->update([
            'password' => Hash::make($fields['password']),
        ]);

        return response()->json([
            'message' => 'Hasło zostało pomyślnie zmienione',
        ]);
    }

    // Wylogowanie
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Wylogowano pomyślnie',
        ]);
    }
}
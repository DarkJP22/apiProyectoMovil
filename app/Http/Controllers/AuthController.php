<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Database\QueryException;
use Exception;

class AuthController extends Controller
{
    public function index()
    {
        try {
            $users = User::select('id', 'name', 'email')->get(); 

            return response()->json([
                'users' => $users
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            // Validación de los datos de entrada
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed'
            ]);

            // Creación del usuario
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Generación del token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ], 201);
        } catch (QueryException $e) {
            // Manejo de errores específicos de base de datos
            if ($e->getCode() == 23000) {
                return response()->json([
                    'message' => 'Ya existe un usuario con ese correo electrónico.'
                ], 400);
            }

            return response()->json([
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            // Errores generales
            return response()->json([
                'message' => 'Error inesperado durante el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            // Validación de las credenciales
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            // Verificar si las credenciales son correctas
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Credenciales inválidas: el correo electrónico o la contraseña son incorrectos.'
                ], 401);
            }

            // Obtener el usuario y generar el token
            $user = User::where('email', $request->email)->first();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ]);
        } catch (Exception $e) {
            // Errores generales durante el login
            return response()->json([
                'message' => 'Error inesperado durante el inicio de sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado
            if (!$request->user()) {
                return response()->json([
                    'message' => 'No se pudo cerrar la sesión: el usuario no está autenticado.'
                ], 401);
            }

            // Eliminar el token actual
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Sesión cerrada correctamente.'
            ]);
        } catch (Exception $e) {
            // Errores generales durante el cierre de sesión
            return response()->json([
                'message' => 'Error al intentar cerrar sesión: ' . $e->getMessage()
            ], 500);
        }
    }
}
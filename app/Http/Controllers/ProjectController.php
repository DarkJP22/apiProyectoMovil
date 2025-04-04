<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Exception;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        try {
            $userId = Auth::id();

            // Obtener los proyectos creados por el usuario
            $projects = Project::where('user_id', $userId)
                ->orWhereHas('tasks', function ($query) use ($userId) {
                    // Si el usuario está asignado a alguna tarea del proyecto
                    $query->where('user_id', $userId);
                })
                ->get();

            return response()->json($projects);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los proyectos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            $project = $request->user()->projects()->create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'user_id' => $request->user()->id,
            ]);

            return response()->json($project, 201);
        } catch (QueryException $e) {
            // Error específico de base de datos (ej. problemas de unicidad, referencias faltantes, etc.)
            return response()->json([
                'message' => 'Error en la base de datos al crear el proyecto: ' . $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            // Error inesperado
            return response()->json([
                'message' => 'Error inesperado al crear el proyecto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Project $project)
    {
        try {
            // Verificamos si el usuario es el creador del proyecto (administrador) o un usuario asignado
            if ($project->user_id !== Auth::id() && !$project->users->contains(Auth::id())) {
                return response()->json([
                    'message' => 'No tienes permisos para ver este proyecto.'
                ], 403);
            }

            return response()->json($project);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el proyecto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Project $project)
    {
        try {
            $this->authorize('update', $project);

            // Solo el creador del proyecto (usuario admin) puede editar el proyecto
            if ($project->user_id !== Auth::id()) {
                return response()->json([
                    'message' => 'No tienes permisos para modificar este proyecto.'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string'
            ]);

            $project->update($validated);

            return response()->json($project);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error en la base de datos al actualizar el proyecto: ' . $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error inesperado al actualizar el proyecto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Project $project)
    {
        try {
            $this->authorize('delete', $project);
            // Solo el creador del proyecto (usuario admin) puede eliminar el proyecto
            if ($project->user_id !== Auth::id()) {
                return response()->json([
                    'message' => 'No tienes permisos para eliminar este proyecto.'
                ], 403);
            }
            $project->delete();
            return response()->json(['message' => 'Proyecto eliminado'], 204);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error en la base de datos al eliminar el proyecto: ' . $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error inesperado al eliminar el proyecto: ' . $e->getMessage()
            ], 500);
        }
    }
}

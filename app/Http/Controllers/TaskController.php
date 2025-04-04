<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\QueryException;
use Exception;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function index(Project $project)
    {
        try {
            $this->authorize('view', $project);
            return response()->json($project->tasks);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las tareas del proyecto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, Project $project)
    {
        try {
            // Verificamos si el usuario tiene permisos para agregar tareas al proyecto
            $this->authorize('update', $project); // Solo si el usuario es el creador del proyecto

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|in:pending,in_progress,completed',
                'user_id' => 'required|exists:users,id', // Asegúrate de que el usuario asignado existe
                'due_date' => 'nullable|date'
            ]);

            // Crear la tarea
            $task = $project->tasks()->create($validated);

            return response()->json($task, 201);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error en la base de datos al crear la tarea: ' . $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error inesperado al crear la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Task $task)
    {
        try {
            $this->authorize('view', $task->project);
            return response()->json($task);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Task $task, Project $project)
    {
        try {
            // Verificamos si el usuario tiene permisos para actualizar la tarea
            if (Auth::id() === $task->user_id || Auth::id() === $project->user_id) {
                // Validamos todos los campos que el usuario puede modificar
                $validated = $request->validate([
                    'status' => 'sometimes|in:pending,in_progress,completed',
                    'title' => 'sometimes|string|max:255',
                    'description' => 'nullable|string|max:255',
                    'user_id' => 'sometimes|exists:users,id'
                ]);

                // Si el usuario no es el admin, solo puede actualizar el estado
                if (Auth::id() !== $project->user_id && isset($validated['status'])) {
                    $task->update(['status' => $validated['status']]);
                } else {
                    // Si es administrador, puede actualizar todos los campos
                    $task->update($validated);
                }

                return response()->json($task);
            } else {
                return response()->json([
                    'message' => 'No tienes permisos para modificar esta tarea.'
                ], 403);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Task $task, Project $project)
    {
        try {
            // Verificamos si el usuario tiene permisos para eliminar la tarea
            if (Auth::id() === $task->user_id || Auth::id() === $project->user_id) {
                $task->delete();

                return response()->json([
                    'message' => 'Tarea eliminada correctamente.'
                ]);
            } else {
                return response()->json([
                    'message' => 'No tienes permisos para eliminar esta tarea.'
                ], 403);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }


    public function search(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string',
                'status' => 'nullable|in:pending,in_progress,completed'
            ]);

            $tasks = Task::where('user_id', Auth::id())
                ->when($request->query, function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->query . '%')
                        ->orWhere('description', 'like', '%' . $request->query . '%');
                })
                ->when($request->status, function ($query) use ($request) {
                    $query->where('status', $request->status);
                })
                ->with('project')
                ->get();

            return response()->json($tasks);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al buscar tareas: ' . $e->getMessage()
            ], 500);
        }
    }
}

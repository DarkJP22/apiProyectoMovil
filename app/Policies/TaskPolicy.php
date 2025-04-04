<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task)
    {
        return $user->id === $task->project->user_id || $user->id === $task->user_id;
    }

    public function update(User $user, Task $task)
    {
        // Si el usuario es el creador del proyecto o está asignado a la tarea
        return $user->id === $task->user_id || $user->id === $task->project->user_id;
    }

    public function delete(User $user, Task $task)
    {
        // Solo el creador del proyecto o el usuario asignado a la tarea puede eliminarla
        return $user->id === $task->user_id || $user->id === $task->project->user_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project)
    {
        return $user->id === $project->user_id
            || $project->tasks()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Project $project)
    {
        return $user->id === $project->user_id;
    }

    public function delete(User $user, Project $project)
    {
        return $user->id === $project->user_id;
    }
}
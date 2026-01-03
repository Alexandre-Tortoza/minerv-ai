<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ai_recommendations;
use Illuminate\Auth\Access\Response;

class AiRecommendationsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ai_recommendations $aiRecommendations): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ai_recommendations $aiRecommendations): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ai_recommendations $aiRecommendations): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ai_recommendations $aiRecommendations): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ai_recommendations $aiRecommendations): bool
    {
        return false;
    }
}

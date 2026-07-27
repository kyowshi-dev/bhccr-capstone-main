<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Get the authenticated user instance
 */
function user(): ?User
{
    return Auth::user();
}

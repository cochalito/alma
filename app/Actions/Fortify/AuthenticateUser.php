<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser
{
    /**
     * Attempt to authenticate the user using username or email.
     * Fortify calls this with the request and expects the authenticated User
     * or a falsy value if authentication fails.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\User|null
     */
    public function __invoke(Request $request): ?User
    {
        $login = $request->input('login');
        $password = $request->input('password');

        if (!$login || !$password) {
            return null;
        }

        // Determine if the login value is an email or a username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Find the user by email or username
        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Owner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->owner) {
            $owner = Owner::create([
                'user_id' => $user->id,
                'business_name' => $user->name . "'s Business",
                'business_license' => 'LIC-' . Str::upper(Str::random(10)),
                'business_address' => '',
                'business_phone' => $user->phone ?? '',
                'business_email' => $user->email,
                'is_verified' => false,
            ]);

            $user->refresh();
        }

        return $next($request);
    }
}

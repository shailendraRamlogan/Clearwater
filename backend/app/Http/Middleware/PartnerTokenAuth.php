<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PartnerTokenAuth
{
    /**
     * Authenticate partner API requests via Bearer token.
     *
     * Tokens are stored in config as 'services.partner_token' (env: PARTNER_TOKEN).
     * Optionally supports multiple comma-separated tokens.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized — no token provided.'], 401);
        }

        $validTokens = array_filter(array_map('trim', explode(',', config('services.partner_token', ''))));

        if (empty($validTokens) || !in_array($token, $validTokens, true)) {
            return response()->json(['error' => 'Unauthorized — invalid token.'], 401);
        }

        // Stamp the request so controllers know who's calling
        $request->attributes->set('partner_user_id', 'partner-api');

        return $next($request);
    }
}

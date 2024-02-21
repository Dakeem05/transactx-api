<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApplicationCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appId = $request->header('AppID');
        $appKey = $request->header('AppKEY');
        $buildKey = $request->header('BuildKey');

        // Check if header contains BuildKey
        if (!$buildKey || $buildKey != env('APP_BUILD_KEY')) {
            return response()->json(['message' => 'Invalid BuildKey. Please ensure you are on the current build version.'], 401);
        }

        // Check if header contains AppID and AppKey
        if (!$appId || !$appKey) {
            return response()->json(['message' => 'AppID and AppKEY are required.'], 401);
        }

        // Validate AppID and AppKey for TransactX Mobile
        if ($appId != env('MOBILE_APP_ID') || $appKey != env('MOBILE_APP_KEY')) {
            return response()->json(['message' => 'Invalid AppID or AppKEY.'], 401);
        }

        return $next($request);
    }
}

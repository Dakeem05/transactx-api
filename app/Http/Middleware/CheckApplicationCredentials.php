<?php

namespace App\Http\Middleware;

use App\Helpers\TransactX;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApplicationCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * 
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appId = $request->header('AppID');
        $appKey = $request->header('AppKEY');
        $buildKey = $request->header('BuildKey');

        // Check if header contains BuildKey
        if (!$buildKey || $buildKey != env('APP_BUILD_KEY')) {
            return TransactX::response(['message' => 'Invalid BuildKey. Please ensure you are on the current build version.'], 401);
        }

        // Check if header contains AppID and AppKey
        if (!$appId || !$appKey) {
            return TransactX::response(['message' => 'AppID and AppKEY are required.'], 401);
        }

        // Validate AppID and AppKey for TransactX Mobile
        if ($appId != env('MOBILE_APP_ID') || $appKey != env('MOBILE_APP_KEY')) {
            return TransactX::response(['message' => 'Invalid AppID or AppKEY.'], 401);
        }

        return $next($request);
    }
}

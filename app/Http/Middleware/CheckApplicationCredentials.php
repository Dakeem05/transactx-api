<?php

namespace App\Http\Middleware;

use App\Http\Resources\TransactXErrorResponse;
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
     * @return TransactXErrorResponse | \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next): TransactXErrorResponse | Response
    {
        $appId = $request->header('AppID');
        $appKey = $request->header('AppKEY');
        $buildKey = $request->header('BuildKey');

        // Check if header contains BuildKey
        if (!$buildKey || $buildKey != env('APP_BUILD_KEY')) {
            return new TransactXErrorResponse([
                'status_code' => 401,
                'message' => 'Invalid BuildKey. Please ensure you are on the current build version.',
            ]);
        }

        // Check if header contains AppID and AppKey
        if (!$appId || !$appKey) {
            return new TransactXErrorResponse([
                'status_code' => 401,
                'message' => 'AppID and AppKEY are required.',
            ]);
        }

        // Validate AppID and AppKey for TransactX Mobile
        if ($appId != env('MOBILE_APP_ID') || $appKey != env('MOBILE_APP_KEY')) {
            return new TransactXErrorResponse([
                'status_code' => 401,
                'message' => 'Invalid AppID or AppKEY.',
            ]);
        }

        return $next($request);
    }
}

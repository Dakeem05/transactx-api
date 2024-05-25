<?php

namespace App\Providers;

use App\Helpers\TransactX;
use App\Models\User;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }


    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $correctCredentials = $this->checkUserCredentials($request->input('username'), $request->input('password'));

            return $correctCredentials ?
                [
                    Limit::perMinute(5)->by($request->input('username'))->response(function (Request $request) {
                        return TransactX::response('Rate limit exceeded. Please try again later!', 429);
                    }),
                ]
                : [
                    Limit::perMinute(5)->by($request->ip)->response(function (Request $request) {
                        return TransactX::response('Rate limit exceeded. Please try again later!', 429);
                    }),
                ];
        });

        RateLimiter::for('otp', function (Request $request) {

            $shortTermLimit = Limit::perMinute(5)->by($request->ip() . '|minute')->response(function (Request $request) {
                return TransactX::response('You have exceeded your limit. Please try again after 60 seconds!', 429);
            });

            $longTermLimit = Limit::perHour(10)->by($request->ip() . '|hour')->response(function (Request $request) {
                return TransactX::response('You have exceeded your limit. Please try again after 60 minutes!', 429);
            });

            $longerTermLimit = Limit::perDay(30)->by($request->ip() . '|day')->response(function (Request $request) {
                return TransactX::response('You have exceeded your limit. Please try again after 24 hours!', 429);
            });

            return [
                $shortTermLimit,
                $longTermLimit,
                $longerTermLimit,
            ];
        });
    }

    /**
     * Checks the user credentials for correctness
     * @param string username
     * @param string password
     * 
     * @return bool | JsonResponse
     */
    private function checkUserCredentials($username, $password): bool | JsonResponse
    {
        try {
            $user = User::where('username', $username)->first();
            if ($user && Hash::check($password, $user->password)) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            return TransactX::response('Error encountered - ' . $e->getMessage(), 500);
        }
    }
}

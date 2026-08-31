<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\Auth\LoginRequest as AppLoginRequest;
use App\Http\Responses\LoginResponse as AppLoginResponse;
use App\Http\Responses\LogoutResponse as AppLogoutResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            FortifyLoginRequest::class,
            AppLoginRequest::class
        );

        $this->app->singleton(
            LoginResponseContract::class,
            AppLoginResponse::class
        );

        $this->app->singleton(
            LogoutResponseContract::class,
            AppLogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // 一般ユーザーログイン
        Fortify::loginView(function () {
            return view('user.user-login');
        });

        // 一般ユーザー会員登録
        Fortify::registerView(function () {
            return view('user.register');
        });

        // ユーザー認証
        Fortify::authenticateUsing(function (Request $request) {

            // 管理者ログインURLか判定
            $adminStatus = $request->is('admin/login');

            $user = User::where('email', $request->email)
                ->where('admin_status', $adminStatus)
                ->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });
    }
}

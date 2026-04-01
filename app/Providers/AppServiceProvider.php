<?php

namespace App\Providers;

use App\Models\Did;
use App\Models\SipAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\DidPolicy;
use App\Policies\SipAccountPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected array $policies = [
        User::class => UserPolicy::class,
        SipAccount::class => SipAccountPolicy::class,
        Did::class => DidPolicy::class,
        Transaction::class => TransactionPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS when behind Cloudflare proxy (Flexible SSL)
        if (request()->header('X-Forwarded-Proto') === 'https' || config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Register MD5-compatible auth provider (for migrated WebLink passwords)
        Auth::provider('md5_compatible', function ($app, array $config) {
            return new \App\Auth\Md5CompatibleUserProvider(
                $app['hash'],
                $config['model']
            );
        });

        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

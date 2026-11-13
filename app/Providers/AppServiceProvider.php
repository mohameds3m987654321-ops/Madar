<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Observers\CourseEnrollmentObserver;
use App\Observers\CourseObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Fortify;
use Laravel\Horizon\Horizon;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // إجبار HTTPS في بيئة الإنتاج على Railway
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Fortify::twoFactorChallengeView('two-factor-challenge');

        User::observe(UserObserver::class);
        CourseEnrollment::observe(CourseEnrollmentObserver::class);
        Course::observe(CourseObserver::class);

        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by('login:'.$request->ip().'|'.($request->input('email') ?? 'guest')),
                Limit::perMinute(30)->by('login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('verification-resend', function (Request $request) {
            return Limit::perMinutes(10, 3)->by(
                $request->user()->getAuthIdentifier().'|'.$request->ip()
            );
        });

        RateLimiter::for('verification-code', function (Request $request) {
            return Limit::perMinute(6)->by(
                $request->user()->getAuthIdentifier().'|'.$request->ip()
            );
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.($request->user()?->id ?? 'guest'));
        });

        RateLimiter::for('coupon-validation', function (Request $request) {
            return [
                Limit::perMinute(10)->by(
                    'coupon:'.$request->ip().'|'.($request->user()?->getAuthIdentifier() ?? 'guest')
                ),
                Limit::perMinute(30)->by('coupon-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('assistant-message', function (Request $request) {
            return Limit::perMinute(20)->by('assistant-message:'.$request->ip());
        });

        RateLimiter::for('assistant-escalation', function (Request $request) {
            return Limit::perHour(3)->by('assistant-escalation:'.$request->ip());
        });

        RateLimiter::for('assistant-conversation', function (Request $request) {
            return Limit::perMinute(60)->by('assistant-conversation:'.$request->ip());
        });

        $markTwoFactorVerified = function ($event): void {
            request()->session()->put(
                'two_factor.authenticated_user_id',
                $event->user->getAuthIdentifier(),
            );
        };

        Event::listen(TwoFactorAuthenticationConfirmed::class, $markTwoFactorVerified);
        Event::listen(ValidTwoFactorAuthenticationCodeProvided::class, $markTwoFactorVerified);

        Horizon::auth(fn (Request $request) => $request->user()?->role === 'admin');
    }
}
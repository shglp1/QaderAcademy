<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\FinalExamAttempt;
use App\Models\PayoutRequest;
use App\Models\Attachment;
use App\Policies\CoursePolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\FinalExamAttemptPolicy;
use App\Policies\PayoutRequestPolicy;
use App\Policies\AttachmentPolicy;

class AppServiceProvider extends ServiceProvider
{
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
        // Register Policies
        $this->registerPolicies();
    }

    /**
     * Register the application's policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);
        Gate::policy(FinalExamAttempt::class, FinalExamAttemptPolicy::class);
        Gate::policy(PayoutRequest::class, PayoutRequestPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
    }
}

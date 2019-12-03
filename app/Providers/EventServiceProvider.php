<?php
namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\ProjectInputs;
use App\Observers\ProjectInputsObserver;
use App\MediaFiles;
use App\Observers\MediaFilesObserver;
use App\Projects;
use App\Observers\ProjectsObserver;

class EventServiceProvider extends ServiceProvider
{

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Projects::observe(ProjectsObserver::class);
        ProjectInputs::observe(ProjectInputsObserver::class);
        MediaFiles::observe(MediaFilesObserver::class);
    }
}

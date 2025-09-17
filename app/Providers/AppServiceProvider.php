<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\Project;
use App\Observers\TaskObserver;
use App\Observers\ProjectObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Enregistrer les observers
        Task::observe(TaskObserver::class);
        Project::observe(ProjectObserver::class);
    }
}

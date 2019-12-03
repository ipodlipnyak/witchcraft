<?php
namespace App\Observers;

use App\ProjectInputs;
use App\Projects;

class ProjectInputsObserver
{

    /**
     * Handle the project inputs "created" event.
     *
     * @param \App\ProjectInputs $projectInputs
     * @return void
     */
    public function created(ProjectInputs $projectInputs)
    {
        /* @var $project Projects */
        $project = $projectInputs->project()
            ->get()
            ->first();
        $project->consistencyCheck();
    }

    /**
     * Handle the project inputs "updated" event.
     *
     * @param \App\ProjectInputs $projectInputs
     * @return void
     */
    public function updated(ProjectInputs $projectInputs)
    {
        /* @var $project Projects */
        $project = $projectInputs->project()
            ->get()
            ->first();
        $project->consistencyCheck();
    }

    /**
     * Handle the project inputs "deleted" event.
     *
     * @param \App\ProjectInputs $projectInputs
     * @return void
     */
    public function deleted(ProjectInputs $projectInputs)
    {
        //
    }

    /**
     * Handle the project inputs "restored" event.
     *
     * @param \App\ProjectInputs $projectInputs
     * @return void
     */
    public function restored(ProjectInputs $projectInputs)
    {
        //
    }

    /**
     * Handle the project inputs "force deleted" event.
     *
     * @param \App\ProjectInputs $projectInputs
     * @return void
     */
    public function forceDeleted(ProjectInputs $projectInputs)
    {
        //
    }
}

<?php
namespace App\Observers;

use App\Projects;
use Illuminate\Support\Facades\Storage;

class ProjectsObserver
{

    /**
     * Handle the projects "created" event.
     *
     * @param \App\Projects $projects
     * @return void
     */
    public function created(Projects $projects)
    {
        //
    }

    /**
     * Handle the projects "updated" event.
     *
     * @param \App\Projects $projects
     * @return void
     */
    public function updated(Projects $projects)
    {
        /* @TODO Maybe it could be used in case when output somehow changed. Not sure if this case possible */
        // if ($projects->isDirty('output')) {
        // $output = $projects->output()
        // ->get()
        // ->first();
        // Storage::disk($output->storage_disk)->exists("{$output->storage_path}/{$output->name}");
        // }
    }

    /**
     * Handle the projects "deleted" event.
     *
     * @param \App\Projects $projects
     * @return void
     */
    public function deleted(Projects $projects)
    {
        //
    }

    /**
     * Handle the projects "restored" event.
     *
     * @param \App\Projects $projects
     * @return void
     */
    public function restored(Projects $projects)
    {
        //
    }

    /**
     * Handle the projects "force deleted" event.
     *
     * @param \App\Projects $projects
     * @return void
     */
    public function forceDeleted(Projects $projects)
    {
        //
    }
}

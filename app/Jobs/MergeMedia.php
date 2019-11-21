<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Projects;
use App\ProjectStatuses;
use App\MediaFiles;
use FFMpeg\Media\Concat;

class MergeMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $task = Projects::query()->where('status', 2)->first();

        $task->progress = ProjectStatuses::INWORK;
        $task->save();

        if (! $this->consistencyCheck()) {
            $task->status = ProjectStatuses::BROKEN;
            $task->save();
            die();
        }

        if ($this->merge($task)) {
            $task->status = ProjectStatuses::DONE;
            $task->save();
        } else {
            $task->status = ProjectStatuses::BROKEN;
            $task->save();
        }
    }

    /**
     * FFmpeg concatenation magic
     *
     * @param Projects $task
     * @return boolean
     * @see Concat
     */
    protected function merge(Projects $task): bool
    {
        /* @var $input MediaFiles */
        foreach ($task->inputs()->get() as $input) {
            /* @TODO work in progress */
            $input->getFullPath();
        }

        $task->progress = 1;
        $task->save();

        return true;
    }

    /**
     * Check if every project inputs coherent with each other
     *
     * @param Projects $task
     * @return bool
     */
    protected function consistencyCheck(Projects $task): bool
    {
        return true;
    }
}

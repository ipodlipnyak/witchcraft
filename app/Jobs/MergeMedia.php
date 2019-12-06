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
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use FFMpeg\FFProbe\DataMapping\Stream;

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
        $output_media = $task->getOutputMediaOrCreate();
        if (! $output_media) {
            return false;
        }
        /* @var $output_model MediaFiles */
        $output_model = $task->output()->first();

        /* @var $input_model MediaFiles */
        foreach ($task->inputs()->get() as $input_model) {
            /* @TODO work in progress */
            $input_model->getFullPath();
            $input_media = $input_model->getMedia();
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
        $output_media = $task->getOutputMediaOrCreate();
        if (! $output_media) {
            return false;
        }

        /* @TODO work in progress */
        /* @var $stream Stream */
        $stream = $output_media->getStreams()
            ->videos()
            ->first();
        $output_ratio = $stream->getDimensions()->getRatio();

        /* @var $input_model MediaFiles */
        foreach ($task->inputs()->get() as $input_model) {
            $input_media = $input_model->getMedia();
        }

        return true;
    }
}

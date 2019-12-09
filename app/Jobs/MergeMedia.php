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
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;
use Illuminate\Database\Eloquent\Collection;
use App\ProjectInputs;
use FFMpeg\Media\Video;
use FFMpeg\Media\AbstractStreamableMedia;

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
        $task = Projects::query()->where('status', ProjectStatuses::TASK)->first();

        $task->status = ProjectStatuses::INWORK;
        $task->save();

        if (! $this->consistencyCheck($task)) {
            $task->status = ProjectStatuses::BROKEN;
            $task->save();
            die();
        }

        if ($this->executeTask($task)) {
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
    protected function executeTask(Projects $task): bool
    {
        $output_media = $task->getOutputMediaOrCreate();
        $inputs_list = $task->inputs()->get();
        if (! $output_media || $inputs_list->isEmpty()) {
            return false;
        }

        /* @var $output_model MediaFiles */
        $output_model = $task->output()->first();

        /* @var $input_model MediaFiles */
        if ($inputs_list->count() == 1) {
            $input_model = $inputs_list->first();
            $this->convertMedia($input_model, $task);
        } elseif ($inputs_list->count() > 1) {
            /*
             * @TODO work in progress:
             * + check if covertMedia and concatenate methods are working properly
             * - maybe maybe should generate new tasks to convert every input to same codec before concatenate them
             */
            $this->concatenate($inputs_list, $task);
            $task->progress = 100;
            $task->save();
            // foreach ($task->inputs()->get() as $input_model) {
            // $input_model->getFullPath();
            // $input_media = $input_model->getMedia();
            // }
        }

        // $task->progress = 1;
        // $task->save();

        return true;
    }

    /**
     * Simple ffmpegs convertion with resize filter
     *
     * @param MediaFiles $input_model
     * @param Projects $task
     */
    protected function convertMedia(MediaFiles $input_model, Projects $task)
    {
        $output_model = $task->output()->first();
        $input_media = $input_model->getMedia();
        $output_model->deleteFiles();

        $format = new X264();
        $format->setAudioCodec('libmp3lame');
        $format->on('progress', function ($video, $format, $percentage) use ($task) {
            $this->makeProgress($percentage, $task);
        });

        $input_media->addFilter(function (VideoFilters $filters) use ($output_model) {
            $filters->resize(new Dimension($output_model->width, $output_model->height));
        })
            ->export()
            ->toDisk($output_model->storage_disk)
            ->inFormat($format)
            ->save("{$output_model->storage_path}/{$output_model->name}");
    }

    /**
     * Concatenate input list
     *
     * @param Collection $inputs_list
     * @param Projects $task
     */
    protected function concatenate(Collection $inputs_list, Projects $task)
    {
        /* @var $output_model MediaFiles */
        $output_model = $task->output()->first();
        $output_model->deleteFiles();

        $sources = [];
        /* @var $input_model MediaFiles */
        foreach ($inputs_list as $input_model) {
            array_push($sources, $input_model->getFullPath());
        }

        if (count($sources) > 0) {
            /* @var $first_input_model MediaFiles */
            $first_input_model = ProjectInputs::query()->where('project', $task->id)
                ->where('priority', 0)
                ->first()
                ->media_file()
                ->first();

            /* @var $first_input_media Video */
            $first_input_media = $first_input_model->getMedia();

            /*
             * @TODO Concatenation are fine.
             * But progress events not working.
             * Should try saveFromSameCodecs method
             * while creating separated tasks for convertion.
             * In that case it would be possible to check the progress,
             * and concatenation tasks wouldn't take much time
             */
            if ($task->isInputsFromDifferentCodecs()) {
                $format = new X264();
                $format->setAudioCodec('libmp3lame');
                $format->on('progress', function ($video, $format, $percentage) use ($task) {
                    $this->makeProgress($percentage, $task);
                });
                $first_input_media->concat($sources)->saveFromDifferentCodecs($format, $output_model->getFullPath());
            } else {
                $first_input_media->concat($sources)->saveFromSameCodecs($output_model->getFullPath());
            }
        }
    }

    /**
     * Should be called on ffmpegs progress event
     *
     * @param int $percentage
     * @param Projects $task
     */
    protected function makeProgress(int $percentage, Projects $task)
    {
        // @TODO WebSocket-server call should be here
        $task->progress = $percentage;
        $task->save();
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

        /* @TODO Work in progress */
        // $task->consistencyCheck();

        $output_model = $task->output()->first();
        $first_input_model = $task->getFirstInput();
        /* @var $output_media Video */
        $output_media = $first_input_model->getMedia();

        /* @var $input_model MediaFiles */
        foreach ($task->inputs()->get() as $input_model) {
            /* @var $input_media Video */
            // $input_media = $input_model->getMedia();
            // $input_video_stream = $input_media->getStreams()
            // ->videos()
            // ->first();
            // $input_audio_stream = $input_media->getStreams()
            // ->audios()
            // ->first();
        }

        return true;
    }
}

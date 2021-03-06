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
use App\Events\ProjectUpdate;
use Illuminate\Support\Facades\Log;
use FFMpeg\Exception\RuntimeException as FFMpegRuntimeException;
use FFMpeg\Filters\Audio\AudioFilters;
use FFMpeg\Filters\Audio\CustomFilter as AudioCustomFilter;
use FFMpeg\Filters\Video\CustomFilter as VideoCustomFilter;

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

        if ($task) {
            $task->status = ProjectStatuses::INWORK;
            $task->save();

            // WebSocket broadcast
            broadcast(new ProjectUpdate($task));

            if (! $this->consistencyCheck($task)) {
                $task->status = ProjectStatuses::BROKEN;
                $task->save();
                broadcast(new ProjectUpdate($task));
                die();
            }

            if ($this->executeTask($task)) {
                $task->status = ProjectStatuses::DONE;
                $task->save();
            } else {
                $task->status = ProjectStatuses::BROKEN;
                $task->save();
            }

            broadcast(new ProjectUpdate($task));
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
            try {
                $this->convertMedia($input_model, $task);
            } catch (FFMpegRuntimeException $e) {
                Log::info($e->getMessage());
                return false;
            }
        } elseif ($inputs_list->count() > 1) {
            /*
             * @TODO work in progress:
             * + check if convertMedia and concatenate methods are working properly
             * - maybe maybe should generate new tasks to convert every input to same codec before concatenate them
             */
            try {
                $this->concatenate($inputs_list, $task);
            } catch (FFMpegRuntimeException $e) {
                Log::info($e->getMessage());
                return false;
            }

            // foreach ($task->inputs()->get() as $input_model) {
            // $input_model->getFullPath();
            // $input_media = $input_model->getMedia();
            // }
        }

        $output_model->refresh()
            ->refreshMediaData()
            ->save();

        // $task->progress = 100;
        // $task->save();

        return true;
    }

    /**
     * Simple ffmpegs convertion with resize filter and progress counter
     *
     * @param MediaFiles $input_model
     * @param Projects $task
     * @param MediaFiles $output_model
     *            if not set output model will be tacken from task
     */
    protected function convertMedia(MediaFiles $input_model, Projects $task, $output_model = null)
    {
        if (! $output_model) {
            $output_model = $task->output()->first();
        }

        $count_inputs = $task->inputs()
            ->get()
            ->count();
        $input_priority = (int) ProjectInputs::query()->where('project', $task->id)
            ->where('media_file', $input_model->id)
            ->value('priority');

        $progress_step = 1 / $count_inputs;
        $progress_offset = $progress_step * $input_priority * 100;

        /* @var $input_media Video */
        $input_media = $input_model->getMedia();
        $output_model->deleteFiles();

        $format = new X264();
        $format->setAudioCodec('libmp3lame');
        $format->on('progress', function ($video, $format, $percentage) use ($task, $progress_offset, $progress_step) {
            $this->makeProgress($progress_offset + $percentage * $progress_step, $task);
        });

        $media_duration = $input_media->getDurationInMiliseconds();
        // would not fade if media shorter then fade duration
        if ($task->concat_fade_duration > 0 && $media_duration > $task->concat_fade_duration * 1000) {
            $fade_duration = round($task->concat_fade_duration / 2, 3);
            $in_fade_start = 0;
            $out_fade_start = round(($media_duration - $fade_duration * 1000) / 1000, 3);

            if ($input_priority > 0) {
                $input_media->addFilter(function (VideoFilters $filters) use ($fade_duration, $in_fade_start) {
                    $filters->custom("fade=type=in:st={$in_fade_start}:d={$fade_duration}");
                });
                $input_media->addFilter(new AudioCustomFilter("afade=type=in:st={$in_fade_start}:d={$fade_duration}"));
            }

            if ($input_priority + 1 < $count_inputs) {
                $input_media->addFilter(function (VideoFilters $filters) use ($fade_duration, $out_fade_start) {
                    $filters->custom("fade=type=out:st={$out_fade_start}:d={$fade_duration}");
                });
                $input_media->addFilter(new AudioCustomFilter("afade=type=out:st={$out_fade_start}:d={$fade_duration}"));
            }
        }

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

        if ($inputs_list->count() > 0) {
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
            if ($task->isTaskShouldApplyFilters()) {
                $this->convertAllInputs($task);
                $inputs_list = $task->inputs()->get();

                $this->concatSameCodecs($inputs_list, $output_model, $first_input_media);

                foreach ($inputs_list as $media_model) {
                    $media_model->delete();
                }
            } else {
                $this->concatSameCodecs($inputs_list, $output_model, $first_input_media);
            }
        }
    }

    protected function concatSameCodecs($inputs_list, $output_model, $first_input_media)
    {
        $sources = [];
        /* @var $input_model MediaFiles */
        foreach ($inputs_list as $input_model) {
            array_push($sources, $input_model->getFullPath());
        }

        $first_input_media->concat($sources)->saveFromSameCodecs($output_model->getFullPath());
    }

    protected function concatDifferentCodecs($inputs_list, $output_model, $first_input_media)
    {
        $sources = [];
        /* @var $input_model MediaFiles */
        foreach ($inputs_list as $input_model) {
            array_push($sources, $input_model->getFullPath());
        }

        $format = new X264();
        $format->setAudioCodec('libmp3lame');
        $first_input_media->concat($sources)->saveFromDifferentCodecs($format, $output_model->getFullPath());
    }

    protected function convertAllInputs(Projects $task)
    {
        $format = new X264();
        $format->setAudioCodec('libmp3lame');

        $inputs_list = $task->inputs()->get();

        /* @var $task_output_model MediaFiles */
        $task_output_model = $task->output()->first();
        $extension = $task_output_model->getFileExtension();

        foreach ($inputs_list as $input_media_model) {
            /* @var $input_media_model MediaFiles */
            $output_media_model = MediaFiles::createOutputTemplate($extension, $input_media_model->user);
            $output_media_model->width = $task_output_model->width;
            $output_media_model->height = $task_output_model->height;

            $this->convertMedia($input_media_model, $task, $output_media_model);

            $output_media_model->refreshMediaData()->save();

            $project_input_model = ProjectInputs::query()->where('project', $task->id)
                ->where('media_file', $input_media_model->id)
                ->first();
            $project_input_model->media_file = $output_media_model->id;
            $project_input_model->save();
        }

        $task->consistencyCheck();
    }

    /**
     * Should be called on ffmpegs progress event
     *
     * @param int $percentage
     * @param Projects $task
     */
    protected function makeProgress(int $percentage, Projects $task)
    {
        $task->progress = $percentage;
        $task->save();

        // WebSocket broadcast
        broadcast(new ProjectUpdate($task));
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

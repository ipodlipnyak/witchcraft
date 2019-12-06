<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Pbmedia\LaravelFFMpeg\Media;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFProbe\DataMapping\Stream;

class Projects extends Model
{

    /**
     * Get output
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function output()
    {
        return $this->hasOne('App\MediaFiles', 'id', 'output');
    }

    /**
     * Get inputs
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function inputs()
    {
        return $this->hasManyThrough('App\MediaFiles', 'App\ProjectInputs', 'project', 'id', 'id', 'media_file');
    }

    /**
     * Check if every project inputs coherent with each other
     *
     * @param Projects $task
     * @return bool
     */
    public function consistencyCheck(): bool
    {
        $output_media = $this->getOutputMediaOrCreate();
        $output_model = $this->output()->first();

        /* @var $output_stream Stream */
        $output_stream = $output_media->getStreams()->videos()->first();

        $input_list = ProjectInputs::query()->where('project', $this->id)->get();

        $brocken_count = 0;
        /* @var $input_model ProjectInputs */
        foreach ($input_list as $input_model) {
            /* @var $input_model MediaFiles */
            $media_model = $input_model->media_file()->first();
            $input_media = $media_model->getMedia();
            /* @var $input_stream Stream */
            $input_stream = $input_media->getStreams()->videos()->first();

            // $input_too_small = ($input_model->width < $output_model->width) && ($input_model->height < $output_model->height);

            if ($input_stream->getDimensions()
                ->getRatio()
                ->calculateHeight($output_model->width) != $output_model->height) {

                $input_model->status = InputStatuses::WRONG_RATIO;
                $input_model->save();
                $brocken_count ++;
            } else {
                $input_model->status = InputStatuses::READY;
                $input_model->save();
            }
        }

        return $brocken_count == 0;
    }

    /**
     * Try to create new output file
     *
     * @return Media|NULL
     */
    protected function createOutputFile(): ?Media
    {
        /* @var $output MediaFiles */
        $output = $this->output()->first();
        /* @var $input MediaFiles */
        $input_id = ProjectInputs::query()->where('project', $this->id)
            ->orderBy('priority', 'ASC')
            ->value('media_file');

        if (! $input_id) {
            Log::info("Project id:{$this->id} have no inputs");
            return null;
        }

        $input = MediaFiles::query()->find($input_id);

        if (Storage::disk($input->storage_disk)->exists("{$input->storage_path}/{$input->name}")) {
            Storage::disk($input->storage_disk)->copy("{$input->storage_path}/{$input->name}", "{$output->storage_path}/{$output->name}");
        } else {
            Log::info("First input id:{$input_id} of project id:{$this->id} have no file");
            return null;
        }

        return $output->getMedia();
    }

    /**
     * Get output media object or create new
     *
     * @return Media|NULL
     */
    public function getOutputMediaOrCreate(): ?Media
    {
        /* @var $output MediaFiles */
        $output = $this->output()->first();
        return $output->getMedia() ?: $this->createOutputFile();
    }
}

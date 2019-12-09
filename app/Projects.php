<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Pbmedia\LaravelFFMpeg\Media;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFProbe\DataMapping\Stream;
use FFMpeg\Media\Video;

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
     * Find out if there is some inputs with different codecs then the first one of them
     *
     * @return bool
     */
    public function isInputsFromDifferentCodecs(): bool
    {
        $this->consistencyCheck();

        $input_list = ProjectInputs::query()->where('project', $this->id)->get();
        if (count($input_list) > 1) {
            return $input_list->where('status', InputStatuses::WRONG_CODEC)
                ->get()
                ->isNotEmpty();
        } else {
            return false;
        }
    }

    /**
     * Check if every project inputs coherent with each other
     *
     * @param Projects $task
     * @return bool
     */
    public function consistencyCheck(): bool
    {
        $output_model = $this->output()->first();
        $first_media_model = $this->getFirstInput();
        /* @var $output_media Video */
        $output_media = $first_media_model->getMedia();

        $input_list = ProjectInputs::query()->where('project', $this->id)->get();

        $brocken_count = 0;
        /* @var $input_model ProjectInputs */
        foreach ($input_list as $input_model) {
            /* @var $media_model MediaFiles */
            $media_model = $input_model->media_file()->first();

            if ($media_model->checkIfSameRatio($output_model)) {
                $input_model->status = InputStatuses::WRONG_RATIO;
                $input_model->save();
                $brocken_count ++;
            } elseif (count($input_list) > 1 && $media_model->checkIfSameCodec($first_media_model)) {
                $input_model->status = InputStatuses::WRONG_CODEC;
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
     * Return first input, eg with priority 0
     *
     * @return MediaFiles|NULL
     */
    public function getFirstInput(): ?MediaFiles
    {
        $input_id = ProjectInputs::query()->where('project', $this->id)
            ->where('priority', 0)
            ->value('media_file');
        if ($input_id) {
            return MediaFiles::query()->find($input_id);
        }

        return null;
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

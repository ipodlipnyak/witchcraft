<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Support\Facades\Storage;
use Pbmedia\LaravelFFMpeg\Media;
use FFMpeg\FFProbe\DataMapping\Stream;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Illuminate\Support\Str;
use FFMpeg\Filters\Frame\FrameFilters;
use Illuminate\Support\Facades\Log;

class MediaFiles extends Model
{

    /**
     * Relation to project which contains this file as an output
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function projectsOutput()
    {
        return $this->belongsTo('App\Projects', 'id', 'output');
    }

    /**
     *
     * @param File $file
     * @param string $storage_path
     * @param string $name
     * @return \App\MediaFiles
     */
    static function createFromFile(File $file)
    {
        $file_model = new MediaFiles();
        $file_model->user = Auth::user()->id;
        $file_model->name = $file->getFilename();
        $file_model->save();

        return $file_model;
    }

    static function createFromMedia(Media $media)
    {
        /* @var $stream Stream */
        try {
            $stream = $media->getStreams()
                ->videos()
                ->first();
        } catch (Exception $e) {
            Log::warning($e->getMessage());
            $stream = null;
        }

        $file = $media->getFile();

        $path = explode('/', $file->getPath());
        $name = array_pop($path);
        $media_storage = implode('/', $path);
        $disk = last(array_filter(explode('/', $file->getDisk()->getPath())));

        $file_model = new MediaFiles();
        $file_model->user = Auth::user()->id;
        $file_model->name = $name;
        $file_model->storage_path = $media_storage;
        $file_model->storage_disk = $disk;
        $file_model->duration = $media->getDurationInSeconds();

        if ($stream) {
            $file_model->height = $stream->getDimensions()->getHeight();
            $file_model->width = $stream->getDimensions()->getWidth();
            $file_model->ratio = floor($stream->getDimensions()
                ->getRatio(true)
                ->getValue() * 100) / 100;
        }

        $file_model->save();
        return $file_model;
    }

    /**
     * Delete thumbnail and media file from storage
     */
    public function deleteFiles()
    {
        // Deleting thumbnail
        $thumbnails_storage = env('FFMPEG_THUMBNAILS_FOLDER');
        if ($this->thumbnail && Storage::disk($this->storage_disk)->exists("{$thumbnails_storage}/{$this->thumbnail}")) {
            Storage::disk($this->storage_disk)->delete("{$thumbnails_storage}/{$this->thumbnail}");
        }

        // Deleting media file
        if (Storage::disk($this->storage_disk)->exists("{$this->storage_path}/{$this->name}")) {
            Storage::disk($this->storage_disk)->delete("{$this->storage_path}/{$this->name}");
        }
    }

    /**
     *
     * @return string
     */
    public function getFullPath()
    {
        $storage_disk = Storage::disk($this->storage_disk)->getAdapter()->getPathPrefix();
        return "{$storage_disk}{$this->storage_path}/{$this->name}";
    }

    /**
     * upload session relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function uploadSession()
    {
        return $this->hasOne('App\UploadSessions', 'id', 'upload_session');
    }

    /**
     * Return full path for thumbnail
     *
     * @return string
     */
    public function getThumbnail(): string
    {
        $thumbnails_storage = env('FFMPEG_THUMBNAILS_FOLDER');
        $result = '';

        // if there is no thumbnail we will try to create new
        if ((! $this->thumbnail || ! Storage::disk($this->storage_disk)->exists("{$thumbnails_storage}/{$this->thumbnail}")) && $this->getMedia()) {
            $thumb_name = Str::random() . ".png";

            // save first frame from media-file to thumbnails storage
            $frame = $this->getMedia()->getFrameFromSeconds(0);
            $frame->addFilter(function (FrameFilters $filters) {
                $filters->custom('scale=200:-1, crop=200:200');
            })
                ->export()
                ->toDisk($this->storage_disk)
                ->save("{$thumbnails_storage}/{$thumb_name}");

            $this->thumbnail = $thumb_name;
            $this->save();
        }

        if ($this->thumbnail && Storage::disk($this->storage_disk)->exists("{$thumbnails_storage}/{$this->thumbnail}")) {
            $storage_disk = Storage::disk($this->storage_disk)->getAdapter()->getPathPrefix();
            $result = "{$storage_disk}{$thumbnails_storage}/{$this->thumbnail}";
        }

        return $result;
    }

    /**
     * Get thumbnail url
     *
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->thumbnail ? "/thumbs/{$this->id}" : "";
    }

    /**
     * Get media file object, which represented by this model
     *
     * @return Media|NULL
     */
    public function getMedia(): ?Media
    {
        if (Storage::disk($this->storage_disk)->exists("{$this->storage_path}/{$this->name}")) {
            return FFmpeg::fromDisk($this->storage_disk)->open("{$this->storage_path}/{$this->name}");
        }

        return null;
    }
}

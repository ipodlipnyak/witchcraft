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
use FFMpeg\Media\Video;

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
            if ($stream->get('tags') && array_key_exists('rotate', $stream->get('tags')) && $stream->get('tags')['rotate'] == 90) {
                $file_model->height = $stream->getDimensions()->getWidth();
                $file_model->width = $stream->getDimensions()->getHeight();
            } else {
                $file_model->height = $stream->getDimensions()->getHeight();
                $file_model->width = $stream->getDimensions()->getWidth();
            }

            $file_model->ratio = floor($stream->getDimensions()
                ->getRatio(true)
                ->getValue() * 100) / 100;
        }

        $file_model->save();
        return $file_model;
    }

    /**
     * Refresh params with media file data
     *
     * @return \App\MediaFiles
     */
    public function refreshMediaData()
    {
        /* @var $media Video */
        $media = $this->getMedia();

        // should clean cache before it would be possible to check if file had been updated
        $cache = $media->getFFProbe()->getCache();
        $cache_id = "-show_streams-{$this->getFullPath()}";
        $cache->delete($cache_id);

        $stream = $media->getStreams()
            ->videos()
            ->first();

        if ($this->getVideoRotateDegree() == 90) {
            $this->height = $stream->getDimensions()->getWidth();
            $this->width = $stream->getDimensions()->getHeight();
        } else {
            $this->height = $stream->getDimensions()->getHeight();
            $this->width = $stream->getDimensions()->getWidth();
        }

        $this->ratio = floor($stream->getDimensions()
            ->getRatio(true)
            ->getValue() * 100) / 100;
        $this->duration = $media->getDurationInSeconds();

        return $this;
    }

    /**
     * Find out if video had a rotate tag and return its value
     *
     * @return number
     */
    public function getVideoRotateDegree()
    {
        /* @var $media Video */
        $media = $this->getMedia();
        $stream = $media->getStreams()
            ->videos()
            ->first();

        if ($stream->get('tags') && array_key_exists('rotate', $stream->get('tags'))) {
            return (int) $stream->get('tags')['rotate'];
        }

        return 0;
    }

    /**
     * Returns the mime type of the file
     *
     * @return string|NULL
     */
    public function getMimeType(): ?string
    {
        if (Storage::disk($this->storage_disk)->exists("{$this->storage_path}/{$this->name}")) {
            $file = new File($this->getFullPath());
            return $file->getMimeType();
        }

        return null;
    }

    /**
     * Gets the file extension
     *
     * @return string|NULL
     */
    public function getFileExtension(): ?string
    {
        if (Storage::disk($this->storage_disk)->exists("{$this->storage_path}/{$this->name}")) {
            $file = new File($this->getFullPath());
            return $file->getExtension();
        }

        return null;
    }

    /**
     * Check audio and video streams for similarities.
     *
     * @param int|MediaFiles $compare_to
     *            id or model of a media file to compare
     * @return bool
     */
    public function checkIfSameCodec($compare_to): bool
    {
        /* @var $compare_to_model MediaFiles */
        if (gettype($compare_to) == 'integer') {
            $compare_to_model = MediaFiles::query()->find($compare_to);
        } elseif ((gettype($compare_to) == 'object') && (get_class($compare_to) == MediaFiles::class)) {
            $compare_to_model = $compare_to;
        }
        /* @var $compare_to_media Video */
        $compare_to_media = $compare_to_model->getMedia();
        $compare_to_video = $compare_to_media->getStreams()
            ->videos()
            ->first();
        $compare_to_audio = $compare_to_media->getStreams()
            ->audios()
            ->first();

        /* @var $this_media Video */
        $this_media = $this->getMedia();
        $this_video = $this_media->getStreams()
            ->videos()
            ->first();
        $this_audio = $this_media->getStreams()
            ->audios()
            ->first();

        // What we should check for similarities
        $video_params_list = [
            'codec_name',
            'avg_frame_rate'
        ];
        $audio_params_list = [
            'codec_name',
            'max_bit_rate'
        ];

        foreach ($video_params_list as $property) {
            if ($this_video->get($property) != $compare_to_video->get($property)) {
                return false;
            }
        }

        foreach ($audio_params_list as $property) {
            if ($this_audio->get($property) != $compare_to_audio->get($property)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if ratio same as for compared model
     *
     * @param bool $force_same_scale
     *            if true check for exact match of height and width params
     * @param int|MediaFiles $compare_to
     *            id or model of a media file to compare
     * @return bool
     */
    public function checkIfSameRatio($compare_to, bool $force_same_scale = false): bool
    {
        /* @var $compare_to_model MediaFiles */
        if (gettype($compare_to) == 'integer') {
            $compare_to_model = MediaFiles::query()->find($compare_to);
        } elseif ((gettype($compare_to) == 'object') && (get_class($compare_to) == MediaFiles::class)) {
            $compare_to_model = $compare_to;
        }

        /* @var $this_media Video */
        $this_media = $this->getMedia();
        $this_video = $this_media->getStreams()
            ->videos()
            ->first();

        if ($this->getVideoRotateDegree() == 90) {
            $match_width = $this_video->getDimensions()->getHeight();
            $calculated_height = $this_video->getDimensions()
                ->getRatio()
                ->calculateWidth($compare_to_model->width);
        } else {
            $match_width = $this_video->getDimensions()->getWidth();
            $calculated_height = $this_video->getDimensions()
                ->getRatio()
                ->calculateHeight($compare_to_model->width);
        }

        if ($calculated_height != $compare_to_model->height) {
            return false;
        }

        if ($force_same_scale && $match_width != $compare_to_model->width) {
            return false;
        }

        return true;
    }

    /**
     * Delete all projects relations
     * 
     * @return \App\MediaFiles
     */
    public function deleteProjectsRelations()
    {
        ProjectInputs::query()->where('media_file', $this->id)->delete();
        
        return $this;
    }


    /**
     * Delete thumbnail and media file from storage
     * 
     * @return \App\MediaFiles
     */
    public function deleteFiles()
    {
        // Deleting thumbnail
        $thumbnails_storage = env('FFMPEG_THUMBNAILS_FOLDER');
        $thumbnail_prop_list = [
            'thumbnail',
            'thumbnail_original_ratio'
        ];
        foreach ($thumbnail_prop_list as $thumbnail_prop) {
            if ($this->$thumbnail_prop && Storage::disk($this->storage_disk)->exists("{$thumbnails_storage}/{$this->$thumbnail_prop}")) {
                Storage::disk($this->storage_disk)->delete("{$thumbnails_storage}/{$this->$thumbnail_prop}");
            }
        }

        // Deleting media file
        if (Storage::disk($this->storage_disk)->exists("{$this->storage_path}/{$this->name}")) {
            Storage::disk($this->storage_disk)->delete("{$this->storage_path}/{$this->name}");
        }
        
        return $this;
    }

    /**
     * Return full path for a media file in local storage
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
     * @param string $ratio
     *            <p>Acceptable values:</p>
     *            <ul>
     *            <li>original - return thumb with original aspect ratio</li>
     *            <li>square - return thumb with square aspect ratio</li>
     *            </ul>
     *            
     *            <p>With wrong value it alwayse square ratio</p>
     * @return string
     */
    public function getThumbnail(string $ratio = 'square'): string
    {
        $thumbnails_storage = env('FFMPEG_THUMBNAILS_FOLDER');
        $result = '';

        /* @var $thumb_source MediaFiles */
        if ($this->projectsOutput()
            ->get()
            ->isNotEmpty()) {
            /* @var $project Projects */
            $project = $this->projectsOutput()->first();
            $thumb_source = $project->getFirstInput();
        } else {
            $thumb_source = $this;
        }

        $original_ratio = $ratio == "original";

        switch ($ratio) {
            case 'original':
                $thumbnail_prop = "thumbnail_original_ratio";
                break;

            case 'square':
            default:
                $thumbnail_prop = "thumbnail";
                break;
        }

        $thumbnail_prop = $original_ratio ? 'thumbnail_original_ratio' : 'thumbnail';

        // if there is no thumbnail we will try to create new
        if ((! $this->$thumbnail_prop || ! Storage::disk($this->storage_disk)->exists("{$thumbnails_storage}/{$this->$thumbnail_prop}")) && $thumb_source && $thumb_source->getMedia()) {
            $thumb_name = Str::random() . ".png";

            // save first frame from media-file to thumbnails storage
            $frame = $thumb_source->getMedia()->getFrameFromSeconds(0);

            $thumb_source_dimensions = $thumb_source->getMedia()
                ->getStreams()
                ->videos()
                ->first()
                ->getDimensions();
            $height = $thumb_source_dimensions->getHeight();
            $width = $thumb_source_dimensions->getWidth();
            $max_scale = 400;

            $scale_size = min([
                $height,
                $width,
                $max_scale
            ]);

            // thumb should not been scaled smaller then $max_scale (400 px)
            $filter_scale = $max_scale == $scale_size ? "scale=$scale_size:-1" : "";

            switch ($ratio) {
                case 'original':
                    $filters_list = [
                        $filter_scale
                    ];
                    break;

                case 'square':
                default:
                    $filter_crop = "crop=$scale_size:$scale_size";
                    $filters_list = [
                        $filter_scale,
                        $filter_crop
                    ];
                    break;
            }

            $filter = implode(", ", $filters_list);

            if ($filter) {
                $frame->addFilter(function (FrameFilters $filters) use ($filter) {
                    $filters->custom($filter);
                });
            }

            $frame->export()
                ->toDisk($this->storage_disk)
                ->save("{$thumbnails_storage}/{$thumb_name}");

            $this->$thumbnail_prop = $thumb_name;
            $this->save();
        }

        $storage_disk = Storage::disk($this->storage_disk)->getAdapter()->getPathPrefix();
        if ($this->$thumbnail_prop && Storage::disk($this->storage_disk)->exists("{$thumbnails_storage}/{$this->$thumbnail_prop}")) {
            $result = "{$storage_disk}{$thumbnails_storage}/{$this->$thumbnail_prop}";
        } else {
            $placeholder = env('IMG_PLACEHOLDER', 'placeholder.svg');
            $result = "{$storage_disk}{$placeholder}";
        }

        return $result;
    }

    /**
     * Get thumbnail url
     *
     * @param bool $original_ratio
     *            if true return thumb with original aspect ratio
     * @return string
     */
    public function getThumbnailUrl(bool $original_ratio = false): string
    {
        $thumbnail_prop = $original_ratio ? 'thumbnail_original_ratio' : 'thumbnail';
        $ratio = $original_ratio ? "/original" : "";
        return $this->$thumbnail_prop ? "/thumbs/{$this->id}{$ratio}" : "";
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

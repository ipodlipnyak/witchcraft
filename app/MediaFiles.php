<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Support\Facades\Storage;
use Pbmedia\LaravelFFMpeg\Media;
use FFMpeg\FFProbe\DataMapping\Stream;

class MediaFiles extends Model
{

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
        $stream = $media->getFirstStream();
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
        $file_model->height = $stream->getDimensions()->getHeight();
        $file_model->width = $stream->getDimensions()->getWidth();
        $file_model->ratio = $stream->getDimensions()->getRatio(true)->getValue();
        $file_model->save();
        return $file_model;
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
}

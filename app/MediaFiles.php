<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\File;

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

    /**
     *
     * @return string
     */
    public function getFullPath()
    {
        $storage_path = storage_path($this->storage_path);
        return "{$storage_path}/{$this->name}";
    }
}

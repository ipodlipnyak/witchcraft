<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\File;

class MediaFiles extends Model
{
    static function createFromFile(File $file) {
        $file_model = new MediaFiles();
        $file_model->user = Auth::user()->id;
        $file_model->name = $file->getFilename();
        $file_model->path = $file->getPath();
        $file_model->save();
        
        return $file_model;
    }
}

<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{

    /**
     * Get output
     */
    public function output()
    {
        return $this->hasOne('App\MediaFiles','id', 'output');
    }
    
    
    /**
     * Get output
     */
    public function inputs()
    {
        return $this->hasManyThrough('App\MediaFiles', 'App\ProjectInputs' , 'project', 'id', 'id', 'media_file');
    }
}

<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

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
}

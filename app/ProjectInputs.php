<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectInputs extends Model
{

    /**
     * Project relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function project()
    {
        return $this->hasOne('App\Projects', 'id', 'project');
    }

    /**
     * Input file relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function media_file()
    {
        return $this->hasOne('App\MediaFiles', 'id', 'media_file');
    }
}

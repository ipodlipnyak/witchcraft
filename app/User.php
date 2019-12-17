<?php
namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    /**
     * Get all media files
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mediaFiles()
    {
        return $this->hasMany('App\MediaFiles', 'user', 'id');
    }

    public function getStorageQuotaBytesAttribute($value)
    {
        return $value !== null ? $value : (int) env('DEFAULT_USER_STORAGE_VOLUME_QUOTA_IN_BYTES', 314572800);
    }

    /**
     * Calculate what left from quota
     *
     * @return number
     */
    public function calcStorageLeft()
    {
        $maximum = $this->storage_quota_bytes;
        $current = $this->calcStorageUsage();

        return $maximum - $current;
    }
    
    /**
     * Calculate usage of storage quota
     *
     * @return number
     */
    public function calcStorageUsage()
    {
        $files_list = $this->mediaFiles()->get();
        
        $total = 0;
        /* @var $file_model MediaFiles */
        foreach ($files_list as $file_model) {
            $total += $file_model->getSize();
        }
        
        return $total;
    }
}

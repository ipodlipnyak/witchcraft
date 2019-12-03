<?php
namespace App\Observers;

use App\MediaFiles;
use Illuminate\Support\Facades\Storage;

class MediaFilesObserver
{

    /**
     * Handle the media files "created" event.
     *
     * @param \App\MediaFiles $mediaFiles
     * @return void
     */
    public function created(MediaFiles $mediaFiles)
    {
        //
    }

    /**
     * Listen to the User created event.
     *
     * @param \App\User $user
     * @return void
     */
    public function updating(MediaFiles $mediaFiles)
    {
        // Move file if projects output name had been changed
        if ($mediaFiles->isDirty('name')) {
            $new_name = $mediaFiles->name;
            $old_name = $mediaFiles->getOriginal('name');

            $project = $mediaFiles->projectsOutput()
                ->get()
                ->first();

            if ($project && Storage::disk($mediaFiles->storage_disk)->exists("{$mediaFiles->storage_path}/{$old_name}")) {
                Storage::disk($mediaFiles->storage_disk)->move("{$mediaFiles->storage_path}/{$old_name}", "{$mediaFiles->storage_path}/{$new_name}");
            }
        }
    }

    /**
     * Handle the media files "updated" event.
     *
     * @param \App\MediaFiles $mediaFiles
     * @return void
     */
    public function updated(MediaFiles $mediaFiles)
    {
        //
    }

    /**
     * Handle the media files "deleted" event.
     *
     * @param \App\MediaFiles $mediaFiles
     * @return void
     */
    public function deleted(MediaFiles $mediaFiles)
    {
        //
    }

    /**
     * Handle the media files "restored" event.
     *
     * @param \App\MediaFiles $mediaFiles
     * @return void
     */
    public function restored(MediaFiles $mediaFiles)
    {
        //
    }

    /**
     * Handle the media files "force deleted" event.
     *
     * @param \App\MediaFiles $mediaFiles
     * @return void
     */
    public function forceDeleted(MediaFiles $mediaFiles)
    {
        //
    }
}

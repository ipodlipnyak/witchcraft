<?php
namespace App\Observers;

use App\MediaFiles;
use Illuminate\Support\Facades\Storage;
use App\Projects;

class MediaFilesObserver
{

    /**
     * Handle the media files "created" event.
     *
     * @param \App\MediaFiles $mediaFile
     * @return void
     */
    public function created(MediaFiles $mediaFile)
    {
        //
    }

    /**
     * Handle the media files "updating" event.
     *
     * @param MediaFiles $mediaFile
     */
    public function updating(MediaFiles $mediaFile)
    {
        // Move file if projects output name had been changed
        if ($mediaFile->isDirty('name')) {
            $new_name = $mediaFile->name;
            $old_name = $mediaFile->getOriginal('name');

            $project = $mediaFile->projectsOutput()
                ->get()
                ->first();

            if ($project && Storage::disk($mediaFile->storage_disk)->exists("{$mediaFile->storage_path}/{$old_name}")) {
                Storage::disk($mediaFile->storage_disk)->move("{$mediaFile->storage_path}/{$old_name}", "{$mediaFile->storage_path}/{$new_name}");
            }
        }
    }

    /**
     * Handle the media files "updated" event.
     *
     * @param \App\MediaFiles $mediaFile
     * @return void
     */
    public function updated(MediaFiles $mediaFile)
    {
        //
    }

    /**
     * Handle the media files "deleted" event.
     *
     * @param \App\MediaFiles $mediaFile
     * @return void
     */
    public function deleted(MediaFiles $mediaFile)
    {
        //
    }

    /**
     * Handle the media files "deleting" event.
     *
     * @param MediaFiles $mediaFile
     */
    public function deleting(MediaFiles $mediaFile)
    {
        // Deleting associated project if this media file is projects output
        /*
         * @TODO IMPORTANT! probably shouldn't do it.
         * For example - it would be better to delete file without destroying project,
         * in case i want to refresh media output for a project
         */
        /* @var $project Projects */
        // $project = $mediaFile->projectsOutput()->first();
        // if ($project) {
        // $project->delete();
        // }

        // Deleting thumbnail
        // $thumbnails_storage = env('FFMPEG_THUMBNAILS_FOLDER');
        // if ($mediaFile->thumbnail && Storage::disk($mediaFile->storage_disk)->exists("{$thumbnails_storage}/{$mediaFile->thumbnail}")) {
        // Storage::disk($mediaFile->storage_disk)->delete("{$thumbnails_storage}/{$mediaFile->thumbnail}");
        // }

        // Deleting media file
        // if (Storage::disk($mediaFile->storage_disk)->exists("{$mediaFile->storage_path}/{$mediaFile->name}")) {
        // Storage::disk($mediaFile->storage_disk)->delete("{$mediaFile->storage_path}/{$mediaFile->name}");
        // }
        $mediaFile->deleteFiles();
    }

    /**
     * Handle the media files "restored" event.
     *
     * @param \App\MediaFiles $mediaFile
     * @return void
     */
    public function restored(MediaFiles $mediaFile)
    {
        //
    }

    /**
     * Handle the media files "force deleted" event.
     *
     * @param \App\MediaFiles $mediaFile
     * @return void
     */
    public function forceDeleted(MediaFiles $mediaFile)
    {
        //
    }
}

<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use App\User;
use Illuminate\Support\Facades\Storage;
use App\MediaFiles;
use Symfony\Component\HttpFoundation\File\File;
use App\UploadSessions;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */

// $result = Str::random(60);



$upload_session_id = 1;
$file_model = '';

$chunks_list = MediaFiles::query()->where('upload_session', $upload_session_id)->orderBy('start_offset','ASC')->get();
// $result = $chunks_list[0]->getFullPath();

$upload_session = UploadSessions::query()->find($upload_session_id);
$file_name = '';

$disk = 'files';
$media_storage = 'media';
$storage_full_prefix = Storage::disk($disk)->getAdapter()->getPathPrefix();
$media_storage_full_path = $storage_full_prefix.$media_storage;




$result = Storage::disk($disk)->getAdapter()->getPathPrefix();

/* @var $chunk_model MediaFiles */
foreach ($chunks_list as $i => $chunk_model) {
    if ($i == 0) {
        $file_name = $chunk_model->name;
        Storage::disk($disk)->copy("{$chunk_model->storage_path}/{$chunk_model->name}", "$media_storage/$file_name");
    } else {
        $chunk = Storage::disk($disk)->get("{$chunk_model->storage_path}/{$chunk_model->name}");
        Storage::disk($disk)->append("$media_storage/$file_name", $chunk);
    }

    if ($i + 1 == count($chunks_list)) {
//         $file = new File("$media_storage_full_path/$file_name");
//         $file_model = MediaFiles::createFromFile($file);
//         $file_model->name_to_display = $upload_session->name;
//         $file_model->storage_path = $media_storage;
//         $file_model->storage_disk = $disk;
//         $file_model->upload_session = request('session_id');
//         $file_model->save();
    }
}

$result = [
//     'result_id' => $file_model->id,
    'cnt_chunks' => count($chunks_list)
];
// response()->json(User::query()->where('name', 'admin')
// ->get('email'))
// ->send();

response()->json($result)->send();
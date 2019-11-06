<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use App\User;
use Illuminate\Support\Facades\Storage;
use App\MediaFiles;
use Symfony\Component\HttpFoundation\File\File;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */

// $result = Str::random(60);

$chunks_list = MediaFiles::query()->where('upload_session', 'OBcm1gZFE5nVtzIqPSizZnQImlifosf9Ljxj58oM4s421uLvvgmiFeUAkaHL')->get();
$result_path = '';

// $result = $chunks_list[0]->getFullPath();

/* @var $chunk_model MediaFiles */
foreach ($chunks_list as $i => $chunk_model) {
    if ($i == 0) {
        $file_name = 'test.mkv';
        $result_path =  storage_path('files/media') . "/$file_name";
        Storage::disk()->copy($chunk_model->getFullPath(), $result_path);
    } else {
        $chunk = Storage::disk()->get($chunk_model->path);
        Storage::disk()->append($result_path, $chunk);
    }
    
    if ($i + 1 == count($chunks_list)){
        $file = new File($result_path);
        $file_model = MediaFiles::createFromFile($file);
    }
}

$result = [
    'result_id' => $file_model->id,
    'cnt_chunks' => count($chunks_list)
];

// response()->json(User::query()->where('name', 'admin')
// ->get('email'))
// ->send();

response()->json($result)->send();
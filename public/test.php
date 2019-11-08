<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use App\User;
use Illuminate\Support\Facades\Storage;
use App\MediaFiles;
use Symfony\Component\HttpFoundation\File\File;
use App\UploadSessions;
use Illuminate\Support\Facades\DB;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */

DB::enableQueryLog();

$result = MediaFiles::query()->with('UploadSession')->find(40);
// $result = $media->uploadSession();

$log = DB::getQueryLog();

$result = config('app.name');

response()->json([
    'result' => $result,
    'db' => $log
])->send();
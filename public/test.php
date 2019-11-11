<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use App\User;
use Illuminate\Support\Facades\Storage;
use App\MediaFiles;
use Symfony\Component\HttpFoundation\File\File;
use App\UploadSessions;
use Illuminate\Support\Facades\DB;
use App\Projects;
use Illuminate\Support\Facades\Auth;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */

DB::enableQueryLog();

// $result = MediaFiles::query()->with('UploadSession')->find(40);

// $o = new MediaFiles();
// $o->name = 'test';
// $o->user = 1;
// $o->save();

// $p = new Project();
// $p->output = $o->id;
// $p->save();

$result = Projects::query()->with('inputs')->get();

// $result = Project::query()->whereHas('output', function($q){
//     $q->where('user',1);
// })->get();
// $result = $media->uploadSession();

$log = DB::getQueryLog();


response()->json([
    'result' => $result,
    'db' => $log
])->send();
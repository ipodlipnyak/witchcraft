<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Redis;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Illuminate\Container\Container;
use Pbmedia\LaravelFFMpeg\Media;
use FFMpeg\FFProbe\DataMapping\Stream;
use League\Flysystem\Filesystem as Driver;
use App\Projects;
use App\MediaFiles;
use App\ProjectInputs;
use FFMpeg\Media\Video;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Events\FFmpegProgress;
use Illuminate\Broadcasting\PendingBroadcast;
use App\Events\ProjectUpdate;
use App\User;
use Illuminate\Support\Facades\Schema;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */
DB::enableQueryLog();

// $project = Projects::query()->find(3);
// $project->progress ++;
// $project->status = 3;
// $project->save();
/* @var $broad_result PendingBroadcast */
// $broad_result = broadcast(new ProjectUpdate($project));
// $result = $broad_result->__destruct();

/* @var $media Video */
/* @var $media_model MediaFiles */
// $media_model = MediaFiles::query()->find(91);
// $media = $media_model->getMedia();
// $result = $media->getStreams()->videos()->first()->all();
// $media_model->refreshMediaData()->save();

// $result = MediaFiles::query()->where('upload_session', '<>', 2)->get();

// $media = MediaFiles::query()->find(2392);
// $result = $media->delete();

/* @var $user User */
// $user = User::query()->first();

// $result = MediaFiles::query()->find(1653)->delete();


$log = DB::getQueryLog();

response()->json([
    'result' => $result,
    'db' => $log
])->send();
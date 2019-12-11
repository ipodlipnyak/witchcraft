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
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */
$container = Container::getInstance();
DB::enableQueryLog();

$project = Projects::query()->find(3);
// $project->progress ++;
// $project->status = 3;
// $project->save();
/* @var $broad_result PendingBroadcast */
$broad_result  = broadcast(new ProjectUpdate($project));
$result = $broad_result->__destruct();

$log = DB::getQueryLog();

response()->json([
    'result' => $result,
    'db' => $log
])->send();
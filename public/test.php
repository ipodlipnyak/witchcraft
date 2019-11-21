<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Illuminate\Container\Container;
use Pbmedia\LaravelFFMpeg\Media;
use FFMpeg\FFProbe\DataMapping\Stream;
use League\Flysystem\Filesystem as Driver;
use App\Projects;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */
$container = Container::getInstance();
DB::enableQueryLog();

/* @var $media Media */
// $media = FFmpeg::fromDisk('files')->open('media/1_nygnA_1.mkv');
// $media->getDurationInSeconds();

/* @var $stream Stream */
// $stream = $media->getFirstStream();
// $file = $media->getFile();
// $disk = $file->getDisk();
/* @var $driver Driver */
// $driver = $disk->getDriver();

// $result = $stream->getDimensions()->getHeight();
// $result = last(array_filter(explode('/', $disk->getPath())));
// $result = $file->getPath();

$task = Projects::query()->where('status', 2)->first();
$result = $task->inputs()->get()[0]->getFullPath(0);


$log = DB::getQueryLog();

response()->json([
    'result' => $result,
    'db' => $log
])->send();
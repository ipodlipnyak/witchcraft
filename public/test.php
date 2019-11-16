<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Illuminate\Container\Container;
use Pbmedia\LaravelFFMpeg\Media;
use FFMpeg\FFProbe\DataMapping\Stream;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */
$container = Container::getInstance();
DB::enableQueryLog();

/* @var $media Media */
$media = FFmpeg::fromDisk('files')->open('media/1_1.mkv');
$media->getDurationInSeconds();

/* @var $stream Stream */
$stream = $media->getFirstStream();
$result = $stream->getDimensions()->getHeight();

$log = DB::getQueryLog();

response()->json([
    'result' => $result,
    'db' => $log
])->send();
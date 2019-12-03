<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Illuminate\Container\Container;
use Pbmedia\LaravelFFMpeg\Media;
use FFMpeg\FFProbe\DataMapping\Stream;
use League\Flysystem\Filesystem as Driver;
use App\Projects;
use App\MediaFiles;
use App\ProjectInputs;
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

/* @var $task Projects */
// $task = Projects::query()->where('status', 2)->first();
// $task = Projects::query()->first();
/* @var $input ProjectInputs */
/* @var $output MediaFiles */
// $output = $task->output()->first();
// $input = $task->inputs()->first();
// $media = $input->getMedia();
// $result = $output->getMedia();

// $result = ProjectInputs::query()->where('project', 1)->orderBy('priority','ASC')->value('media_file');
// $media = $task->getOutputMediaOrCreate();
// $result = $media->getDurationInSeconds();
// $input = ProjectInputs::query()->where('project', $task->id)->get()[0];
// $result = $input->media_file()->first();

// $output = MediaFiles::query()->find(1);
// $task = $output->projectsOutput()->get()->first();
// $result = $task;
// $result = $task->consistencyCheck();

$result = env('FFMPEG_OUTPUT_FOLDER');


$log = DB::getQueryLog();

response()->json([
    'result' => $result,
    'db' => $log
])->send();
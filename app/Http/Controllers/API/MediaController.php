<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MediaFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\UploadSessions;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File;
use Pbmedia\LaravelFFMpeg\FFMpegFacade as FFMpeg;
use Pbmedia\LaravelFFMpeg\Media;
use FFMpeg\FFProbe\DataMapping\Stream;

class MediaController extends Controller
{

    protected $disk = '';

    protected $media_storage = '';

    protected $output_storage = '';

    public function __construct()
    {
        $this->disk = env('FFMPEG_DISK');
        $this->media_storage = env('FFMPEG_INPUT_FOLDER');
        $this->output_storage = env('FFMPEG_OUTPUT_FOLDER');
    }

    /**
     * Display a listing of the uploaded files
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = [
            'status' => 'error'
        ];

        $files_list = MediaFiles::query()
            ->where('user', Auth::user()->id)
            ->whereIn('storage_path', [
            'media',
            'output'
        ])
            ->where('storage_disk', 'files')
            ->whereNull('start_offset')
            ->whereNotNull('duration')
            ->get();

        /* @var $file_model MediaFiles */
        foreach ($files_list as &$file_model) {
            $file_model->size = $file_model->getSize();
        }

        if ($files_list) {
            $result['status'] = 'success';
            $result['files'] = $files_list;
        }

        return response()->json($result);
    }

    /**
     * Chunked upload
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        switch (request('phase')) {
            case 'start':
                $upload_session = new UploadSessions();
                $upload_session->name = request('name');
                $upload_session->mime_type = request('mime_type');
                $upload_session->size = request('size');
                $upload_session->save();

                $result = [
                    'data' => [
                        'session_id' => $upload_session->id,
                        'end_offset' => 500000 // maximum size of chunk
                    ],
                    'status' => 'success'
                ];
                break;

            case 'upload':
                if (MediaFiles::query()->where('upload_session', request('session_id'))
                    ->where('start_offset', request('start_offset'))
                    ->get()
                    ->isEmpty()) {

                    $file = $request->file('chunk');
                    $storage_path = 'chunks';
                    $file = $file->move(Storage::disk($this->disk)->getAdapter()
                        ->getPathPrefix() . $storage_path);

                    $file_model = MediaFiles::createFromFile($file);
                    $file_model->storage_path = $storage_path;
                    $file_model->storage_disk = $this->disk;
                    $file_model->upload_session = request('session_id');
                    $file_model->start_offset = request('start_offset');
                    $file_model->save();
                }

                $result = [
                    'status' => 'success'
                ];
                break;

            case 'finish':
                $upload_session_id = request('session_id');
                $chunks_list = MediaFiles::query()->where('upload_session', $upload_session_id)
                    ->orderBy('start_offset', 'ASC')
                    ->get();

                $upload_session = UploadSessions::query()->find($upload_session_id);
                $random_prefix = Str::random(5);
                $file_name = "{$upload_session_id}_{$random_prefix}_{$upload_session->name}";

                $storage_full_prefix = Storage::disk($this->disk)->getAdapter()->getPathPrefix();
                $media_storage_full_path = $storage_full_prefix . $this->media_storage;

                /* @var $chunk_model MediaFiles */
                foreach ($chunks_list as $i => $chunk_model) {
                    $chunk_path = "{$chunk_model->storage_path}/{$chunk_model->name}";
                    // if file exist we assume that uploading had been interrupted. In that case first chunk should be merged with this file
                    if ($i == 0 && ! Storage::disk($this->disk)->exists("{$this->media_storage}/{$file_name}")) {
                        Storage::disk($this->disk)->copy($chunk_path, "{$this->media_storage}/{$file_name}");
                    } else {
                        $chunk = Storage::disk($this->disk)->get($chunk_path);

                        if (! file_put_contents("{$media_storage_full_path}/{$file_name}", $chunk, FILE_APPEND | LOCK_EX)) {
                            $msg = "Chunk #$i {$chunk_model->name} can not be merged into $file_name";
                            logger($msg);
                            return [
                                'status' => 'error',
                                'msg' => $msg
                            ];
                        }
                    }

                    if ($i + 1 == count($chunks_list)) {
                        $file = new File("{$media_storage_full_path}/{$file_name}");

                        /* @var $media Media */
                        $media = FFmpeg::fromDisk($this->disk)->open("{$this->media_storage}/{$file_name}");
                        /* @var $stream Stream */
                        $stream = $media->getStreams()
                            ->videos()
                            ->first();

                        $file_model = MediaFiles::createFromMedia($media);
                        $file_model->label = $upload_session->name;
                        $file_model->upload_session = request('session_id');
                        $file_model->save();
                    }

                    // remove chunk from disk and db
                    Storage::disk($this->disk)->delete($chunk_path);
                    $chunk_model->delete();
                }

                $result = [
                    'status' => 'success'
                ];
                break;

            default:
                $file = $request->file('file');
                $random_prefix = Str::random(5);
                $file_name = "{$random_prefix}_{$file->getClientOriginalName()}";

                $upload_session = new UploadSessions();
                $upload_session->name = $file->getClientOriginalName();
                $upload_session->mime_type = $file->getMimeType();
                $upload_session->size = $file->getSize();
                $upload_session->save();

                $file = $file->move(Storage::disk($this->disk)->getAdapter()
                    ->getPathPrefix() . $this->media_storage, $file_name);

                /* @var $media Media */
                $media = FFmpeg::fromDisk($this->disk)->open("{$this->media_storage}/{$file_name}");

                $file_model = MediaFiles::createFromMedia($media);

                /*
                 * @TODO This is weird.
                 * Not sure if i need to store same value in separate tables.
                 * Except when i have one to many relations between upload session and uploaded chunkes.
                 * Maybe should to reconsider it later
                 */
                $file_model->label = $upload_session->name;

                $file_model->upload_session = $upload_session->id;
                $file_model->save();

                $result = [
                    'status' => 'success'
                ];
                break;
        }

        return response()->json($result);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove file from storage and db
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $result = [
            'status' => 'error'
        ];

        $file_model = MediaFiles::query()->find(request('id'));

        if ($file_model->delete()) {
            $result['status'] = 'success';
        }

        return response()->json($result);
    }
}

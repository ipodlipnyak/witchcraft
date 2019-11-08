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

class MediaClerkController extends Controller
{

    /**
     * Display a listing of the uploaded files
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = MediaFiles::query()->with('UploadSession')
            ->where('user', Auth::user()->id)
            ->where('start_offset', null)
            ->get();
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
        $disk = 'files';

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
                $file = $request->file('chunk');
                $storage_path = 'chunks';
                $file = $file->move(Storage::disk($disk)->getAdapter()
                    ->getPathPrefix() . $storage_path);

                $file_model = MediaFiles::createFromFile($file);
                $file_model->storage_path = $storage_path;
                $file_model->storage_disk = $disk;
                $file_model->upload_session = request('session_id');
                $file_model->start_offset = request('start_offset');
                $file_model->save();

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
                $file_name = "{$upload_session_id}_{$upload_session->name}";

                $media_storage = 'media';
                $storage_full_prefix = Storage::disk($disk)->getAdapter()->getPathPrefix();
                $media_storage_full_path = $storage_full_prefix . $media_storage;

                /* @var $chunk_model MediaFiles */
                foreach ($chunks_list as $i => $chunk_model) {
                    $chunk_path = "{$chunk_model->storage_path}/{$chunk_model->name}";
                    // if file exist we assume that uploading had been interrupted. In that case first chunk should be merged with this file
                    if ($i == 0 && ! Storage::disk($disk)->exists("$media_storage/$file_name")) {
                        Storage::disk($disk)->copy($chunk_path, "$media_storage/$file_name");
                    } else {
                        $chunk = Storage::disk($disk)->get($chunk_path);

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
                        $file = new File("$media_storage_full_path/$file_name");
                        $file_model = MediaFiles::createFromFile($file);
                        $file_model->name_to_display = $upload_session->name;
                        $file_model->storage_path = $media_storage;
                        $file_model->storage_disk = $disk;
                        $file_model->upload_session = request('session_id');
                        $file_model->save();
                    }

                    // remove chunk from disk and db
                    Storage::disk($disk)->delete($chunk_path);
                    $chunk_model->delete();
                }

                $result = [
                    'status' => 'success'
                ];
                break;

            default:
                $file = $request->file();
                $storage_path = 'media';
                $file = $file->move(Storage::disk($disk)->getAdapter()
                    ->getPathPrefix() . $storage_path);

                $file_model = MediaFiles::createFromFile($file);
                $file_model->storage_path = $storage_path;
                $file_model->storage_disk = $disk;
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
        if (Storage::disk($file_model->storage_disk)->delete("{$file_model->storage_path}/{$file_model->name}")) {
            if ($file_model->delete()) {
                $result['status'] = 'success';
            }
        }

        return response()->json($result);
    }
}

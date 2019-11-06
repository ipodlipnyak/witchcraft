<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MediaFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\UploadSessions;
use Illuminate\Support\Facades\Storage;

class MediaClerkController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = 'hi';
        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
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
                        'end_offset' => 500000
                    ],
                    'status' => 'success'
                ];
                break;

            case 'upload':
                $file = $request->file('chunk');
                $storage_path = 'chunks';
                $disk = 'files';
                $file = $file->move(Storage::disk($disk)->getAdapter()->getPathPrefix().$storage_path);
                
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

                $result = [
                    'status' => 'success'
                ];
                break;

            default:
                $file = $request->file();
                $storage_path = 'media';
                $disk = 'files';
                $file = $file->move(Storage::disk($disk)->getAdapter()->getPathPrefix().$storage_path);
                
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
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

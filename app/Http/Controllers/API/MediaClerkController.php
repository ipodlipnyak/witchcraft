<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MediaFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
                $result = [
                    'data' => [
                        'session_id' => Str::random(60),
                        'end_offset' => 500000
                    ],
                    'status' => 'success'
                ];
                break;

            case 'upload':
                $file = $request->file('chunk');

                $file = $file->move(storage_path('files/chunks'));

                $file_model = MediaFiles::createFromFile($file);
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
                $file = $file->move(storage_path('files/media'));
                MediaFiles::createFromFile($file);
                
                $result = [
                    'status' => 'success'
                ];
                break;
        }

        if (request('phase') == 'start') {}

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

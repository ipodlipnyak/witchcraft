<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\MediaFiles;

class MediaClerk extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('main', [
            'api_token' => Auth::user()->api_token
        ]);
    }

    /**
     * Show thumbnail for specified media file.
     * If current logged user is its owner
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|boolean
     */
    public function getThumb()
    {
        /* @var $media_model MediaFiles */
        $media_model = MediaFiles::query()->find(request('mediaId'));

        $ratio = request('ratio') ?: 'square';

        if ($media_model && $media_model->user == Auth::user()->id && $media_model->getThumbnail($ratio)) {
            return response()->file($media_model->getThumbnail($ratio));
        } else {
            return abort(404);
        }
    }

    /**
     * Show specified media file.
     * If current logged user is its owner
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|boolean
     */
    public function getMedia()
    {
        /* @var $media_model MediaFiles */
        $media_model = MediaFiles::query()->find(request('mediaId'));
        if ($media_model && $media_model->user == Auth::user()->id && $media_model->getMedia()) {
            return response()->file($media_model->getFullPath());
        } else {
            return abort(404);
        }
    }
}

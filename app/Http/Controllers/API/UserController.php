<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\User;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /* @var $request Request */
        $request = request();
        return $request->user();
    }

    public function getStorageQuota()
    {
        $result = [
            'status' => 'error'
        ];

        /* @var $request Request */
        $request = request();
        
        /* @var $user User */
//         $user = User::query()->find(Auth::user()->id);

        try {
            $result['maximum'] = Auth::user()->storage_quota_bytes;
            $result['usage'] = Auth::user()->calcStorageUsage();
            $result['left'] = $result['maximum'] - $result['usage'];
            $result['status'] = 'success';
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

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
        //
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

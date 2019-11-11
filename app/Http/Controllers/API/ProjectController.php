<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects;
use Illuminate\Support\Facades\Auth;
use App\MediaFiles;

class ProjectController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Projects::query()->whereHas('output', function ($q) {
            $q->where('user', Auth::user()->id);
        })
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $result = [
            'status' => 'error'
        ];

        $output = new MediaFiles();
        $output->name = $request->get('output');
        $output->save();

        $project = new Projects();
        $project->output = $output->id;
        $project->save();

        if ($project->id) {
            $result['status'] = 'success';
            $result['id'] = $project->id;
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
        $result = [
            'status' => 'error'
        ];

        $project = Projects::query()->with('output')->find(request('id'));

        if ($project) {
            $result['status'] = 'success';
            $result['project'] = $project;
        }

        return response()->json($result);
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

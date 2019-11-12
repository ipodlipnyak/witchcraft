<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects;
use Illuminate\Support\Facades\Auth;
use App\MediaFiles;
use App\ProjectInputs;
use phpDocumentor\Reflection\Project;

class ProjectsController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Projects::query()->with('output')
            ->whereHas('output', function ($q) {
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
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $result = [
            'status' => 'error'
        ];

        $project = Projects::query()->with('output')
            ->with('inputs')
            ->find(request('id'));

        foreach ($project['inputs'] as &$input) {
            $input['priority'] = ProjectInputs::query()->where('project', $project['id'])
                ->where('media_file', $input['id'])
                ->value('priority');
        }

        if ($project) {
            $result['status'] = 'success';
            $result['project'] = $project;
        }

        return response()->json($result);
    }

    /**
     * Get available files for specified project
     *
     * @return \Illuminate\Http\Response
     */
    public function getAvailableFiles()
    {
        $result = [
            'status' => 'error'
        ];
        
        $poject = Projects::query()->with('inputs')->find(request('id'));
        $files = MediaFiles::query()->where('user',Auth::user()->id)->whereKeyNot($poject['inputs']->pluck('id'))->get();
        
        if ($files) {
            $result['status'] = 'success';
            $result['files'] = $files;
        }
        
        return response()->json($result);
    }
    
    public function getInputs() {
        $result = [
            'status' => 'error'
        ];
        
        $project = Projects::query()->with('inputs')->find(request('id'));
        
        foreach ($project['inputs'] as &$input) {
            $input['priority'] = ProjectInputs::query()->where('project', $project['id'])
            ->where('media_file', $input['id'])
            ->value('priority');
        }
        
        $files = $project['inputs']->sortBy('priority')->values()->all();
        
        if ($files) {
            $result['status'] = 'success';
            $result['files'] = $files;
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

<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects;
use Illuminate\Support\Facades\Auth;
use App\MediaFiles;
use App\ProjectInputs;
use phpDocumentor\Reflection\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $project = new Projects();
        $project->save();

        $output = new MediaFiles();

        $user_id = Auth::user()->id;
        $suffix = Str::random(5);
        $extension = $request->get('extension');
        $output->name = "{$user_id}_{$project->id}_{$suffix}.{$extension}";

        $output->storage_path = env('FFMPEG_OUTPUT_FOLDER');
        $output->storage_disk = env('FFMPEG_DISK');
        $output->user = $user_id;
        $output->width = $request->get('width');
        $output->height = $request->get('height');
        $output->label = $request->get('label');

        if ($output->save()) {
            $project->output = $output->id;
            $project->save();

            if ($project->id) {
                $result['status'] = 'success';
                $result['id'] = $project->id;
            }
        } else {
            $project->delete();
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

        $project = Projects::query()->with('inputs')->find(request('id'));
        $files_to_skip = $project['inputs']->pluck('id')->toArray();
        array_push($files_to_skip, $project['output']);
        $files = MediaFiles::query()->where('user', Auth::user()->id)
            ->whereKeyNot($files_to_skip)
            ->where('start_offset', null)
            ->get();

        if ($files) {
            $result['status'] = 'success';
            $result['files'] = $files;
        }

        return response()->json($result);
    }

    /**
     * Get all available files for new project
     *
     * @return \Illuminate\Http\Response
     */
    public function getAvailableFilesAll()
    {
        $result = [
            'status' => 'error'
        ];

        $files = MediaFiles::query()->where('user', Auth::user()->id)
            ->where('start_offset', null)
            ->get();

        if ($files) {
            $result['status'] = 'success';
            $result['files'] = $files;
        }

        return response()->json($result);
    }

    /**
     * Get project's inputs
     *
     * @return \Illuminate\Http\Response
     */
    public function getInputs()
    {
        $result = [
            'status' => 'error'
        ];

        $project = Projects::query()->with('inputs')->find(request('id'));

        foreach ($project['inputs'] as &$input) {
            $project_input_model = ProjectInputs::query()->where('project', $project['id'])
                ->where('media_file', $input['id'])
                ->first();
            $input['priority'] = $project_input_model->priority;
            $input['status'] = $project_input_model->status;
        }

        $files = $project['inputs']->sortBy('priority')
            ->values()
            ->all();

        if ($files) {
            $result['status'] = 'success';
            $result['files'] = $files;
        }

        return response()->json($result);
    }

    /**
     * Update project's inputs
     *
     * @return \Illuminate\Http\Response
     */
    public function updateInputs()
    {
        $result = [
            'status' => 'error'
        ];
        /* @var $project Projects */
        $project = Projects::query()->find(request('id'));

        if ($project) {
            $first_input_old = ProjectInputs::query()->where('project', $project->id)
                ->where('priority', 0)
                ->first();
            ProjectInputs::query()->where('project', $project->id)->delete();

            foreach (request('inputs') as $key => $id) {
                // If first input had been changed we should recreate output media file
                if (($key == 0) && ($first_input_old) && ($first_input_old->media_file != $id)) {
                    /*
                     * @TODO It is working.
                     * Yet there is some problem with cache in vue.
                     * Should find how to clean it and reload thumbnail after output update.
                     */
                    MediaFiles::query()->find($project->output)->deleteFiles();
                }
                $input = new ProjectInputs();
                $input->project = $project->id;
                $input->media_file = $id;
                $input->priority = $key;
                $input->save();
            }

            $result['status'] = 'success';
        }

        return response()->json($result);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $result = [
            'status' => 'error'
        ];

        /* @var $project Projects */
        $project = Projects::query()->find(request('id'));
        $output = $project->output()
            ->get()
            ->first();
        if ($output) {
            $output->label = request('label');
            $output->width = request('width');
            $output->height = request('height');

            $new_extension = request('extension');
            if ($new_extension) {
                $new_output_name = preg_replace('/\.[^.]+$/', '.', $output->name) . $new_extension;

                if ($output->name != $new_output_name) {
                    if (Storage::disk($output->storage_disk)->exists("{$output->storage_path}/{$new_output_name}")) {
                        $result['message'] = 'File name already in use';
                        return response()->json($result);
                    }

                    if (Storage::disk($output->storage_disk)->exists("{$output->storage_path}/{$output->name}")) {
                        Storage::disk($output->storage_disk)->move("{$output->storage_path}/{$output->name}", "{$output->storage_path}/{$new_output_name}");
                    }

                    $output->name = $new_output_name;
                }
            }

            if ($output->save()) {
                $result['status'] = 'success';
            }
        }

        return response()->json($result);
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

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InsertIntoUsers extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'test',
            'email' => 'test@test.test',
            'password' => Hash::make('1'),
            'api_token' => Str::random(60),
        ]);
        
        DB::table('media_files')->insert([
            'id' => 1,
            'storage_path' => 'output',
            'storage_disk' => 'files',
            'name_to_display' => 'demoOutput',
            'name' => 'demo.mkv',
            'user' => 1
        ]);
        
        DB::table('projects')->insert([
            'id' => 1,
            'output' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

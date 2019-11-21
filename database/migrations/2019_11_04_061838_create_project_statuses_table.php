<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProjectStatusesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });

        DB::table('project_statuses')->insert([
            [
                'id' => 1,
                'name' => 'TEMPLATE'
            ],
            [
                'id' => 2,
                'name' => 'TASK'
            ],
            [
                'id' => 3,
                'name' => 'INWORK'
            ],
            [
                'id' => 4,
                'name' => 'DONE'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_statuses');
    }
}

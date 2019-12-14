<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateInputStatusesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('input_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('desc');
        });

        DB::table('input_statuses')->insert([
            [
                'id' => 1,
                'name' => 'READY',
                'desc' => 'All good, ready to go'
            ],
            [
                'id' => 2,
                'name' => 'BROKEN',
                'desc' => 'Something is wrong with this one input'
            ],
            [
                'id' => 3,
                'name' => 'WRONG RATIO',
                'desc' => 'Input ratio is incompatible with current project'
            ],
            [
                'id' => 4,
                'name' => 'WRONG CODEC',
                'desc' => 'Input codec is different from first input of the project'
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
        Schema::dropIfExists('input_statuses');
    }
}

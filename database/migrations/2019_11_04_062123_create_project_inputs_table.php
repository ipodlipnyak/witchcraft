<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectInputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_inputs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project')->unsigned();
            $table->bigInteger('media_file')->unsigned();
            $table->integer('priority')->nullable();
            $table->timestamps();
            
            $table->foreign('project')->references('id')->on('projects');
            $table->foreign('media_file')->references('id')->on('media_files')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_inputs');
    }
}

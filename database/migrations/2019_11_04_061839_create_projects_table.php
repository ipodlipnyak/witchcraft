<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('output')->unsigned()->nullable();
            $table->bigInteger('status')->unsigned()->default(1);
            $table->float('progress')->default(0);
            $table->float('concat_fade_duration')->default(0);
            $table->timestamps();
            
            $table->foreign('output')->references('id')->on('media_files')->onDelete('cascade');
            $table->foreign('status')->references('id')->on('project_statuses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}

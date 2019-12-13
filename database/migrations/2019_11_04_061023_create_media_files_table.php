<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user');
            $table->string('name');
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('thumbnail_original_ratio')->nullable();
            $table->string('label')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->float('ratio')->nullable();
            $table->integer('duration')->nullable();
            $table->bigInteger('upload_session')->unsigned()->nullable();
            $table->integer('start_offset')->nullable();
            $table->timestamps();
            
            $table->foreign('upload_session')->references('id')->on('upload_sessions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_files');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveClassTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_class', function (Blueprint $table) {
            $table->id();
            $table->integer('course_id');
            $table->integer('created_by');
            $table->string('live_class_name')->nullable();
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->integer('preferred_platform')->nullable();
            $table->longText('link_to_live_class')->nullable();
            $table->longText('note_to_students')->nullable();
            $table->integer('is_completed')->nullable();
            $table->integer('is_deleted')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('live_class');
    }
}

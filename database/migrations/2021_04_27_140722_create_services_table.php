<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->integer('service_type')->nullable();
            $table->double('price_per_hour')->nullable();
            $table->string('communication_channel')->nullable();
            $table->double('price_per_session')->nullable();
            $table->string('session_duration')->nullable();
            $table->longText('full_course_description')->nullable();
            $table->longText('session_video_link')->nullable();
            $table->integer('is_free_session')->nullable();
            $table->integer('no_of_students')->nullable();
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
        Schema::dropIfExists('services');
    }
}

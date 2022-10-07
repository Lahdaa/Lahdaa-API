<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseContentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_content', function (Blueprint $table) {
            $table->id();
            $table->integer('course_id')->nullable();
            $table->string('course_content_name')->nullable();
            $table->string('estimated_time')->nullable();
            $table->longText('attachment_file_url')->nullable();
            $table->longText('attachment_link')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('is_approved')->nullable();
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
        Schema::dropIfExists('course_content');
    }
}

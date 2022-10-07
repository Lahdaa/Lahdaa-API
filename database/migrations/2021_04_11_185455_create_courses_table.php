<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_name');
            $table->string('class_size')->nullable();
            $table->integer('course_category')->nullable();
            $table->longText('outcome')->nullable();
            $table->string('course_availability')->nullable();
            $table->longText('thumbnail_file_url')->nullable();
            $table->longText('promo_video_url')->nullable();
            $table->double('price')->nullable();
            $table->integer('is_discounted')->nullable();
            $table->double('discount_price')->nullable();
            $table->integer('is_published')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('is_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
}

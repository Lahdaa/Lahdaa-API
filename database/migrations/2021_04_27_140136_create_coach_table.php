<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoachTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coach', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('rating')->nullable();
            $table->longText('professional_title')->nullable();
            $table->longText('profile_picture_url')->nullable();
            $table->longText('profile_url')->nullable();
            $table->longText('about_you')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_acounnt_name')->nullable();
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
        Schema::dropIfExists('coach');
    }
}

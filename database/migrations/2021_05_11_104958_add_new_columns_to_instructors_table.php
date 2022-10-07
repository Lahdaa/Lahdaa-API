<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToInstructorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instructors', function (Blueprint $table) {
            //
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->longText('professional_title')->nullable();
            $table->longText('profile_picture_url')->nullable();
            $table->longText('profile_url')->nullable();
            $table->longText('about_you')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instructors', function (Blueprint $table) {
            //
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->longText('professional_title')->nullable();
            $table->longText('profile_picture_url')->nullable();
            $table->longText('profile_url')->nullable();
            $table->longText('about_you')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name');
            $table->integer('posted_by');
            $table->string('years_of_experience')->nullable();
            $table->text('description')->nullable();
            $table->string('expected_salary')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_logo_url')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->string('application_mode')->nullable();
            $table->string('job_type')->nullable();
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
        Schema::dropIfExists('jobs');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('course_id');
            $table->integer('user_id');
            $table->double('amount')->nullable();
            $table->longText('details')->nullable();
            $table->integer('is_instructor_payed')->nullable();
            $table->double('admin_revenue')->nullable();
            $table->double('instructor_revenue')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('channel')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('transaction');
    }
}

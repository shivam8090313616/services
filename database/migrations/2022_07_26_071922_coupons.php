<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Coupons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('coupon_code', 50);
            $table->enum('coupon_type', array('percent', 'flat'));
            $table->double('min_bil_amt');
            $table->integer('coupon_value');
            $table->integer('max_disc');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('status')->default(0);
            $table->boolean('trash')->default(0);
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
        //
    }
}

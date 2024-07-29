<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PubUserPayoutModes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pub_user_payout_modes', function (Blueprint $table) {
            $table->id();
            $table->double('payout_id');
            $table->string('payout_name', 50);
            $table->string('publisher_id', 200);
            $table->double('pub_withdrawl_limit');
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

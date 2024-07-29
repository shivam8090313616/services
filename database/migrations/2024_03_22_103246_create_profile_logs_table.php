<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uid',30);
            $table->dateTime('switcher_login')->nullable();
            $table->longText('profile_data');
            $table->integer('user_type')->comment('1=>Advertiser,2=>Publisher,3-Become Login');
            $table->integer('action')->comment('1=>Create, 2=>Update');
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
        Schema::dropIfExists('profile_logs');
    }
}

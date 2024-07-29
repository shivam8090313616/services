<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BannedIps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 50);
            $table->enum('type', array('real', 'proxy', 'suspicious'));
            $table->string('desc', 255);
            $table->string('advertiser_id', 50);
            $table->boolean('status')->default(0);
            $table->enum('blocked_by', array('admin', 'user'));
            $table->timestamps();
        });
    }
}

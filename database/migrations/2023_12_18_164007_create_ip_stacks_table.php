<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpStacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ip_stacks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ip_addrs',100);
            $table->string('continent_code',50);
            $table->string('continent_name');
            $table->string('country_code',50);
            $table->string('country_name');
            $table->string('region_code',50);
            $table->string('region_name');
            $table->string('city');
            $table->integer('zip');
            $table->string('time_zone',100);
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
        Schema::dropIfExists('ip_stacks');
    }
}

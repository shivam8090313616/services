<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PubRateMasters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pub_rate_masters', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->string('category_name');
            $table->integer('country_id');
            $table->string('country_name');
            $table->double('cpc');
            $table->double('cpm');
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

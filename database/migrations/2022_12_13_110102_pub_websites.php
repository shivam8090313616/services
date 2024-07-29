<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PubWebsites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pub_websites', function (Blueprint $table) {
            $table->id();
            $table->string('web_code', 50);
            $table->string('web_name', 20);
            $table->text('site_url');
            $table->string('auth_code', 50);
            $table->boolean('verify')->default(0);
            $table->boolean('status')->default(1);
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

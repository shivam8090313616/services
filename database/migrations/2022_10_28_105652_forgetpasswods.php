<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Forgetpasswods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forgetpasswods', function (Blueprint $table) {
            $table->id();
			$table->string('key_auth', 100);
            $table->dateTime('start');
            $table->dateTime('end');
            $table->date('date');
            $table->string('link_url', 300);
            $table->boolean('status')->default(0);
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

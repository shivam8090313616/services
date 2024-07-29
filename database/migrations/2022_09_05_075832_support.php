<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Support extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('support', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 100);
            $table->string('category', 50);
            $table->string('sub_category', 50);
            $table->string('subject', 100);
            $table->string('message', 100);
            $table->string('file', 100);
            $table->string('priority', 100);
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

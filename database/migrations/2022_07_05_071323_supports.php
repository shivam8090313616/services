<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Supports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supports', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 50)->unique();
            $table->string('ticket_no', 50)->unique();
            $table->string('category', 50);
            $table->string('sub_category', 100);
            $table->string('sub_category', 50);
            $table->string('subject', 50);
            $table->text('message');
            $table->text('file');
            $table->boolean('status')->default(1);
            $table->string('priority', 100);
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

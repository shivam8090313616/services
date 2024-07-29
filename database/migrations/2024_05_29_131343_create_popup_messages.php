<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePopupMessages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('popup_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title',70);
            $table->string('sub_title',70);
            $table->string('image',20);
            $table->string('message',300);
            $table->string('btn_content',60);
            $table->string('btn_link');
            $table->enum('account_type',['1','2'])->comment('1=>Advertiser,2=>Publisher');
            $table->enum('popup_type',['1','2'])->comment('1=>Congratulations,2=>Insufficient Balance');
            $table->tinyInteger('status')->default(0)->comment('1->Enable,0->Disable');
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
        Schema::dropIfExists('popup_messages');
    }
}

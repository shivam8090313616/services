<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeaderMessageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('header_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('header_content',255);
            $table->string('slider_content',300);
            $table->enum('account_type',['1','2'])->comment('1=>Advertiser,2=>Publisher');
            $table->string('content_speed',10);
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
        Schema::dropIfExists('header_messages');
    }
}

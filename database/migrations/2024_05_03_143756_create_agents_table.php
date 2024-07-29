<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('agent_id',16)->unique();
            $table->string('name',50);
            $table->string('email',100);
            $table->string('contact_no',20)->nullable();
            $table->string('profile_image',50)->nullable();
            $table->string('skype_id',50)->nullable();
            $table->string('telegram_id',50)->nullable();
            $table->tinyInteger('status')->default(1)->comment("active:1,inactive:0");
            $table->tinyInteger('trash')->default(0)->comment("0-Not deleted,1-deleted");
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
        Schema::dropIfExists('agents');
    }
}

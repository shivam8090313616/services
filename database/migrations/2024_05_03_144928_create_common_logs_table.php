<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommonLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('common_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uid',60);
            $table->string('type_module',60);
            $table->text('description');
            $table->enum('user_type', ['0', '1'])->default('0')->comment("0 => User, 1 => Admin");
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
        Schema::dropIfExists('common_logs');
    }
}

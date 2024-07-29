<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Admins extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 20);
            $table->string('username', 200);
            $table->string('role_id', 50);
            $table->string('roles_id', 200)->nullable();
            $table->integer('user_type')->comment('emp-2,admin1');
            $table->string('email', 100)->unique();
            $table->string('password', 250);
            $table->string('otp', 10)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->rememberToken();
            $table->integer('status')->nullable()->comment('0:Pending,1:Active');
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

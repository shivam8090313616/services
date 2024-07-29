<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 50)->unique();
            $table->enum('auth_provider', array('facebook','google','twitter','custom','7api'));
            $table->string('user_name', 50);
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 100)->unique();
            $table->string('phone', 20);
            $table->string('wallet', 20);
            $table->string('website_category', 100);
            $table->string('password', 250);
            $table->string('address_line1', 100);
            $table->string('address_line2', 100);
            $table->string('city', 20);
            $table->string('state', 25);
            $table->string('country', 25);
            $table->boolean('status')->default(0);
            $table->boolean('trash')->default(0);
            $table->boolean('user_type')->default(0);
            $table->boolean('account_type')->default(0);
            $table->boolean('ac_verified')->default(0);
            $table->string('verify_code', 10);
            $table->string('ip', 250);
            $table->dateTime('last_login');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}

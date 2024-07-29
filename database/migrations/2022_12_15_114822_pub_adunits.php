<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PubAdunits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pub_adunits', function (Blueprint $table) {
            $table->id();
            $table->string('ad_code', 50);
            $table->string('web_id', 20);
            $table->string('uid', 20);
            $table->string('ad_name', 50);
            $table->string('ad_type', 20);
            $table->text('site_url');
            $table->string('website_category', 20);
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

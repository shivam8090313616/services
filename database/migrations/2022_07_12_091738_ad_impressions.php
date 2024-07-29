<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdImpressions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->string('impression_id', 50)->uniqid();
            $table->string('campaign_id', 50);
            $table->string('advertiser_code', 50);
            $table->string('device_type', 20);
            $table->string('device_os', 20);
            $table->string('ip_addr', 50);
            $table->string('country', 30);
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

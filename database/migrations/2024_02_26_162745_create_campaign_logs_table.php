<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SsCampaignLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uid');
            $table->string('campaign_type');
            $table->string('campaign_id');
            $table->json('campaign_data');
            $table->integer('action')->nullable()->comment('Create-1, Update-2');
            $table->integer('user_type')->comment('User-1, Admin-2');
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

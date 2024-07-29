<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Campaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id', 50)->unique();
            $table->string('device_type', 100);
            $table->string('device_os', 50);
            $table->string('campaign_name', 60);
            $table->boolean('campaign_type')->default(0);
            $table->string('ad_title', 60);
            $table->string('ad_description', 160);
            $table->text('target_url');
            $table->text('conversion_url');
            $table->integer('website_category');
            $table->double('daily_budget');
            $table->string('country_ids');
            $table->text('country_name');
            $table->boolean('priority')->default(0);
            $table->boolean('status')->default(0);
            $table->boolean('trash')->default(0);
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
        //
    }
}

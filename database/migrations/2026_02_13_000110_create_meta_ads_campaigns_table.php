<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetaAdsCampaignsTable extends Migration
{
    public function up()
    {
        Schema::create('meta_ads_campaigns', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->string('meta_campaign_id', 64)->index();
            $table->string('name')->nullable();
            $table->string('status', 32)->nullable();
            $table->string('objective', 64)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'meta_campaign_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('meta_ads_campaigns');
    }
}

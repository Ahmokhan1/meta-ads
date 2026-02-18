<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetaAdsMetricsDailyTable extends Migration
{
    public function up()
    {
        Schema::create('meta_ads_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->index();
            $table->integer('campaign_id')->index();
            $table->date('date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->decimal('spend', 12, 2)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('cpc', 12, 4)->default(0);
            $table->decimal('cpm', 12, 4)->default(0);
            $table->decimal('frequency', 8, 4)->default(0);
            $table->unsignedBigInteger('leads')->default(0);
            $table->unsignedBigInteger('purchases')->default(0);
            $table->decimal('purchase_value', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['campaign_id', 'date']);
            $table->index(['date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('meta_ads_metrics_daily');
    }
}

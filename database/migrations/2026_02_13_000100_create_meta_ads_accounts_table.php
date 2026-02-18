<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetaAdsAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('meta_ads_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('ad_account_id', 64)->unique();
            $table->string('name')->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->string('time_zone', 64)->nullable();
            $table->string('status', 32)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meta_ads_accounts');
    }
}

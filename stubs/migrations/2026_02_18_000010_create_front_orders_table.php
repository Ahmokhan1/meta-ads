<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrontOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('front_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->string('fbclid', 255)->nullable();
            $table->string('fbc', 255)->nullable();
            $table->string('fbp', 255)->nullable();
            $table->string('meta_campaign_id', 64)->nullable();
            $table->string('meta_ad_set_id', 64)->nullable();
            $table->string('meta_ad_id', 64)->nullable();

            $table->timestamps();

            $table->index('lead_id');
            $table->index('fbclid');
            $table->index('fbc');
            $table->index('meta_campaign_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('front_orders');
    }
}

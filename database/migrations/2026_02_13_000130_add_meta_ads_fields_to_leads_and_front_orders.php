<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetaAdsFieldsToLeadsAndFrontOrders extends Migration
{
    public function up()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');
        $ordersTable = config('meta_ads.orders_table', 'front_orders');

        Schema::table($leadsTable, function (Blueprint $table) {
            $table->string('fbclid', 255)->nullable()->after('google_conversion_error');
            $table->string('fbc', 255)->nullable()->after('fbclid');
            $table->string('fbp', 255)->nullable()->after('fbc');
            $table->string('meta_campaign_id', 64)->nullable()->after('fbp');
            $table->string('meta_ad_set_id', 64)->nullable()->after('meta_campaign_id');
            $table->string('meta_ad_id', 64)->nullable()->after('meta_ad_set_id');

            $table->index('fbclid');
            $table->index('fbc');
            $table->index('meta_campaign_id');
            $table->index('meta_ad_set_id');
            $table->index('meta_ad_id');
        });

        Schema::table($ordersTable, function (Blueprint $table) {
            $table->string('fbclid', 255)->nullable()->after('google_keyword');
            $table->string('fbc', 255)->nullable()->after('fbclid');
            $table->string('fbp', 255)->nullable()->after('fbc');
            $table->string('meta_campaign_id', 64)->nullable()->after('fbp');
            $table->string('meta_ad_set_id', 64)->nullable()->after('meta_campaign_id');
            $table->string('meta_ad_id', 64)->nullable()->after('meta_ad_set_id');

            $table->index('fbclid');
            $table->index('fbc');
            $table->index('meta_campaign_id');
        });
    }

    public function down()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');
        $ordersTable = config('meta_ads.orders_table', 'front_orders');

        Schema::table($leadsTable, function (Blueprint $table) {
            $table->dropIndex(['fbclid']);
            $table->dropIndex(['fbc']);
            $table->dropIndex(['meta_campaign_id']);
            $table->dropIndex(['meta_ad_set_id']);
            $table->dropIndex(['meta_ad_id']);

            $table->dropColumn([
                'fbclid',
                'fbc',
                'fbp',
                'meta_campaign_id',
                'meta_ad_set_id',
                'meta_ad_id',
            ]);
        });

        Schema::table($ordersTable, function (Blueprint $table) {
            $table->dropIndex(['fbclid']);
            $table->dropIndex(['fbc']);
            $table->dropIndex(['meta_campaign_id']);

            $table->dropColumn([
                'fbclid',
                'fbc',
                'fbp',
                'meta_campaign_id',
                'meta_ad_set_id',
                'meta_ad_id',
            ]);
        });
    }
}

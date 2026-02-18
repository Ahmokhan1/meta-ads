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

        if (Schema::hasTable($leadsTable)) {
            $afterGoogleError = Schema::hasColumn($leadsTable, 'google_conversion_error');
            Schema::table($leadsTable, function (Blueprint $table) use ($afterGoogleError) {
                $fbclid = $table->string('fbclid', 255)->nullable();
                if ($afterGoogleError) {
                    $fbclid->after('google_conversion_error');
                }
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
        }

        if (Schema::hasTable($ordersTable)) {
            $afterGoogleKeyword = Schema::hasColumn($ordersTable, 'google_keyword');
            Schema::table($ordersTable, function (Blueprint $table) use ($afterGoogleKeyword) {
                $fbclid = $table->string('fbclid', 255)->nullable();
                if ($afterGoogleKeyword) {
                    $fbclid->after('google_keyword');
                }
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
    }

    public function down()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');
        $ordersTable = config('meta_ads.orders_table', 'front_orders');

        if (Schema::hasTable($leadsTable)) {
            Schema::table($leadsTable, function (Blueprint $table) use ($leadsTable) {
                if (Schema::hasColumn($leadsTable, 'fbclid')) {
                    $table->dropIndex(['fbclid']);
                }
                if (Schema::hasColumn($leadsTable, 'fbc')) {
                    $table->dropIndex(['fbc']);
                }
                if (Schema::hasColumn($leadsTable, 'meta_campaign_id')) {
                    $table->dropIndex(['meta_campaign_id']);
                }
                if (Schema::hasColumn($leadsTable, 'meta_ad_set_id')) {
                    $table->dropIndex(['meta_ad_set_id']);
                }
                if (Schema::hasColumn($leadsTable, 'meta_ad_id')) {
                    $table->dropIndex(['meta_ad_id']);
                }

                $dropColumns = [];
                foreach (['fbclid', 'fbc', 'fbp', 'meta_campaign_id', 'meta_ad_set_id', 'meta_ad_id'] as $col) {
                    if (Schema::hasColumn($leadsTable, $col)) {
                        $dropColumns[] = $col;
                    }
                }
                if ($dropColumns) {
                    $table->dropColumn($dropColumns);
                }
            });
        }

        if (Schema::hasTable($ordersTable)) {
            Schema::table($ordersTable, function (Blueprint $table) use ($ordersTable) {
                if (Schema::hasColumn($ordersTable, 'fbclid')) {
                    $table->dropIndex(['fbclid']);
                }
                if (Schema::hasColumn($ordersTable, 'fbc')) {
                    $table->dropIndex(['fbc']);
                }
                if (Schema::hasColumn($ordersTable, 'meta_campaign_id')) {
                    $table->dropIndex(['meta_campaign_id']);
                }

                $dropColumns = [];
                foreach (['fbclid', 'fbc', 'fbp', 'meta_campaign_id', 'meta_ad_set_id', 'meta_ad_id'] as $col) {
                    if (Schema::hasColumn($ordersTable, $col)) {
                        $dropColumns[] = $col;
                    }
                }
                if ($dropColumns) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }
}

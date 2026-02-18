<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetaAdsFieldsToLeadsTable extends Migration
{
    public function up()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');

        if (!Schema::hasTable($leadsTable)) {
            return;
        }

        $afterGoogleError = Schema::hasColumn($leadsTable, 'google_conversion_error');

        Schema::table($leadsTable, function (Blueprint $table) use ($leadsTable, $afterGoogleError) {
            if (!Schema::hasColumn($leadsTable, 'fbclid')) {
                $fbclid = $table->string('fbclid', 255)->nullable();
                if ($afterGoogleError) {
                    $fbclid->after('google_conversion_error');
                }
            }
            if (!Schema::hasColumn($leadsTable, 'fbc')) {
                $table->string('fbc', 255)->nullable()->after('fbclid');
            }
            if (!Schema::hasColumn($leadsTable, 'fbp')) {
                $table->string('fbp', 255)->nullable()->after('fbc');
            }
            if (!Schema::hasColumn($leadsTable, 'meta_campaign_id')) {
                $table->string('meta_campaign_id', 64)->nullable()->after('fbp');
            }
            if (!Schema::hasColumn($leadsTable, 'meta_ad_set_id')) {
                $table->string('meta_ad_set_id', 64)->nullable()->after('meta_campaign_id');
            }
            if (!Schema::hasColumn($leadsTable, 'meta_ad_id')) {
                $table->string('meta_ad_id', 64)->nullable()->after('meta_ad_set_id');
            }
            if (!Schema::hasColumn($leadsTable, 'meta_conversion_sent_at')) {
                $table->timestamp('meta_conversion_sent_at')->nullable()->after('meta_ad_id');
            }
            if (!Schema::hasColumn($leadsTable, 'meta_conversion_error')) {
                $table->text('meta_conversion_error')->nullable()->after('meta_conversion_sent_at');
            }

        });
    }

    public function down()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');

        if (!Schema::hasTable($leadsTable)) {
            return;
        }

        Schema::table($leadsTable, function (Blueprint $table) use ($leadsTable) {
            $dropColumns = [];
            foreach ([
                'fbclid',
                'fbc',
                'fbp',
                'meta_campaign_id',
                'meta_ad_set_id',
                'meta_ad_id',
                'meta_conversion_sent_at',
                'meta_conversion_error',
            ] as $col) {
                if (Schema::hasColumn($leadsTable, $col)) {
                    $dropColumns[] = $col;
                }
            }

            if ($dropColumns) {
                $table->dropColumn($dropColumns);
            }
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    public function up()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');

        if (Schema::hasTable($leadsTable)) {
            $addFbclid = !Schema::hasColumn($leadsTable, 'fbclid');
            $addFbc = !Schema::hasColumn($leadsTable, 'fbc');
            $addFbp = !Schema::hasColumn($leadsTable, 'fbp');
            $addMetaCampaign = !Schema::hasColumn($leadsTable, 'meta_campaign_id');
            $addMetaAdSet = !Schema::hasColumn($leadsTable, 'meta_ad_set_id');
            $addMetaAd = !Schema::hasColumn($leadsTable, 'meta_ad_id');
            $addMetaSentAt = !Schema::hasColumn($leadsTable, 'meta_conversion_sent_at');
            $addMetaError = !Schema::hasColumn($leadsTable, 'meta_conversion_error');

            if (
                $addFbclid || $addFbc || $addFbp ||
                $addMetaCampaign || $addMetaAdSet || $addMetaAd ||
                $addMetaSentAt || $addMetaError
            ) {
                Schema::table($leadsTable, function (Blueprint $table) use (
                    $addFbclid,
                    $addFbc,
                    $addFbp,
                    $addMetaCampaign,
                    $addMetaAdSet,
                    $addMetaAd,
                    $addMetaSentAt,
                    $addMetaError
                ) {
                    if ($addFbclid) {
                        $table->string('fbclid', 255)->nullable();
                        $table->index('fbclid');
                    }
                    if ($addFbc) {
                        $table->string('fbc', 255)->nullable();
                        $table->index('fbc');
                    }
                    if ($addFbp) {
                        $table->string('fbp', 255)->nullable();
                    }
                    if ($addMetaCampaign) {
                        $table->string('meta_campaign_id', 64)->nullable();
                        $table->index('meta_campaign_id');
                    }
                    if ($addMetaAdSet) {
                        $table->string('meta_ad_set_id', 64)->nullable();
                        $table->index('meta_ad_set_id');
                    }
                    if ($addMetaAd) {
                        $table->string('meta_ad_id', 64)->nullable();
                        $table->index('meta_ad_id');
                    }
                    if ($addMetaSentAt) {
                        $table->timestamp('meta_conversion_sent_at')->nullable();
                    }
                    if ($addMetaError) {
                        $table->text('meta_conversion_error')->nullable();
                    }
                });
            }

            return;
        }

        Schema::create($leadsTable, function (Blueprint $table) {
            $table->id();
            $table->string('candidate_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('fbclid', 255)->nullable();
            $table->string('fbc', 255)->nullable();
            $table->string('fbp', 255)->nullable();
            $table->string('meta_campaign_id', 64)->nullable();
            $table->string('meta_ad_set_id', 64)->nullable();
            $table->string('meta_ad_id', 64)->nullable();
            $table->timestamp('meta_conversion_sent_at')->nullable();
            $table->text('meta_conversion_error')->nullable();

            $table->timestamps();

            $table->index('fbclid');
            $table->index('fbc');
            $table->index('meta_campaign_id');
            $table->index('meta_ad_set_id');
            $table->index('meta_ad_id');
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

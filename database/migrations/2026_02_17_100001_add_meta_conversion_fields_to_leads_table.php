<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetaConversionFieldsToLeadsTable extends Migration
{
    public function up()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');

        if (!Schema::hasTable($leadsTable)) {
            return;
        }

        Schema::table($leadsTable, function (Blueprint $table) {
            $table->timestamp('meta_conversion_sent_at')->nullable()->after('meta_ad_id');
            $table->text('meta_conversion_error')->nullable()->after('meta_conversion_sent_at');
        });
    }

    public function down()
    {
        $leadsTable = config('meta_ads.leads_table', 'leads');

        if (!Schema::hasTable($leadsTable)) {
            return;
        }

        Schema::table($leadsTable, function (Blueprint $table) {
            $table->dropColumn([
                'meta_conversion_sent_at',
                'meta_conversion_error',
            ]);
        });
    }
}

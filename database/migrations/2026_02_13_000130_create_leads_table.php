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
            return;
        }

        Schema::create($leadsTable, function (Blueprint $table) {
            $table->id();
            $table->string('candidate_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('course_interested')->nullable();
            $table->json('course_ids')->nullable();
            $table->date('enrolment_date')->nullable();

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

        Schema::dropIfExists($leadsTable);
    }
}

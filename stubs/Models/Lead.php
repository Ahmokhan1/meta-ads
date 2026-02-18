<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'candidate_name',
        'contact_number',
        'email',
        'course_interested',
        'course_ids',
        'enrolment_date',
        'fbclid',
        'fbc',
        'fbp',
        'meta_campaign_id',
        'meta_ad_set_id',
        'meta_ad_id',
        'meta_conversion_sent_at',
        'meta_conversion_error',
    ];

    protected $casts = [
        'course_ids' => 'array',
        'enrolment_date' => 'date',
        'meta_conversion_sent_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontOrder extends Model
{
    protected $fillable = [
        'lead_id',
        'total_amount',
        'fbclid',
        'fbc',
        'fbp',
        'meta_campaign_id',
        'meta_ad_set_id',
        'meta_ad_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];
}

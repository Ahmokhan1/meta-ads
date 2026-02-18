<?php

namespace Ahmokhan1\MetaAds\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaAdsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'meta_campaign_id',
        'name',
        'status',
        'objective',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(MetaAdsAccount::class, 'account_id');
    }

    public function metrics()
    {
        return $this->hasMany(MetaAdsMetricDaily::class, 'campaign_id');
    }
}



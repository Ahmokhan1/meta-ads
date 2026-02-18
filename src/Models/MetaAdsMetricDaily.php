<?php

namespace Ahmokhan1\MetaAds\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaAdsMetricDaily extends Model
{
    use HasFactory;

    protected $table = 'meta_ads_metrics_daily';

    protected $fillable = [
        'account_id',
        'campaign_id',
        'date',
        'impressions',
        'clicks',
        'reach',
        'spend',
        'ctr',
        'cpc',
        'cpm',
        'frequency',
        'leads',
        'purchases',
        'purchase_value',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'ctr' => 'decimal:4',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'frequency' => 'decimal:4',
        'purchase_value' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(MetaAdsAccount::class, 'account_id');
    }

    public function campaign()
    {
        return $this->belongsTo(MetaAdsCampaign::class, 'campaign_id');
    }
}



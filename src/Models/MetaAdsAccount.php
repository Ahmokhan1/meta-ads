<?php

namespace T4E\MetaAds\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaAdsAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_account_id',
        'name',
        'currency_code',
        'time_zone',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function campaigns()
    {
        return $this->hasMany(MetaAdsCampaign::class, 'account_id');
    }
}

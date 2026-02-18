<?php

namespace Ahmokhan1\MetaAds\Services;

use Carbon\Carbon;
use RuntimeException;
use Ahmokhan1\MetaAds\Models\MetaAdsAccount;
use Ahmokhan1\MetaAds\Models\MetaAdsCampaign;
use Ahmokhan1\MetaAds\Models\MetaAdsMetricDaily;

class MetaAdsSyncService
{
    private MetaAdsApiClient $client;

    public function __construct(MetaAdsApiClient $client)
    {
        $this->client = $client;
    }

    public function syncMetrics(Carbon $from, Carbon $to): int
    {
        $adAccountId = config('meta_ads.ad_account_id');
        if (!$adAccountId) {
            throw new RuntimeException('META_ADS_AD_ACCOUNT_ID is required.');
        }

        $account = $this->resolveAccount((string) $adAccountId);
        $this->syncAccountDetails($account, (string) $adAccountId);

        $rows = $this->client->fetchCampaignDailyInsights((string) $adAccountId, $from, $to);

        $campaignCache = [];
        $updated = 0;

        foreach ($rows as $row) {
            $campaignData = $row['campaign'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $segments = $row['segments'] ?? [];

            $metaCampaignId = isset($campaignData['id']) ? (string) $campaignData['id'] : '';
            $date = $segments['date'] ?? null;

            if ($metaCampaignId === '' || !$date) {
                continue;
            }

            if (!isset($campaignCache[$metaCampaignId])) {
                $campaign = MetaAdsCampaign::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'meta_campaign_id' => $metaCampaignId,
                    ],
                    [
                        'name' => $campaignData['name'] ?? null,
                        'status' => $campaignData['status'] ?? null,
                        'objective' => $campaignData['objective'] ?? null,
                        'start_date' => $campaignData['startDate'] ?? null,
                        'end_date' => $campaignData['endDate'] ?? null,
                    ]
                );

                $campaignCache[$metaCampaignId] = $campaign->id;
            }

            MetaAdsMetricDaily::updateOrCreate(
                [
                    'campaign_id' => $campaignCache[$metaCampaignId],
                    'date' => $date,
                ],
                [
                    'account_id' => $account->id,
                    'impressions' => (int) ($metrics['impressions'] ?? 0),
                    'clicks' => (int) ($metrics['clicks'] ?? 0),
                    'reach' => (int) ($metrics['reach'] ?? 0),
                    'spend' => (float) ($metrics['spend'] ?? 0),
                    'ctr' => (float) ($metrics['ctr'] ?? 0),
                    'cpc' => (float) ($metrics['cpc'] ?? 0),
                    'cpm' => (float) ($metrics['cpm'] ?? 0),
                    'frequency' => (float) ($metrics['frequency'] ?? 0),
                    'leads' => (int) ($metrics['leads'] ?? 0),
                    'purchases' => (int) ($metrics['purchases'] ?? 0),
                    'purchase_value' => (float) ($metrics['purchaseValue'] ?? 0),
                ]
            );

            $updated++;
        }

        $account->last_synced_at = now();
        $account->save();

        return $updated;
    }

    private function resolveAccount(string $adAccountId): MetaAdsAccount
    {
        return MetaAdsAccount::firstOrCreate([
            'ad_account_id' => $this->normalizeAccountId($adAccountId),
        ]);
    }

    private function syncAccountDetails(MetaAdsAccount $account, string $adAccountId): void
    {
        $details = $this->client->fetchAccount($adAccountId);

        if (!$details) {
            return;
        }

        $account->name = $details['name'] ?? $account->name;
        $account->currency_code = $details['currencyCode'] ?? $account->currency_code;
        $account->time_zone = $details['timeZone'] ?? $account->time_zone;
        $account->status = $details['status'] ?? $account->status;
        $account->save();
    }

    private function normalizeAccountId(string $id): string
    {
        $normalized = trim($id);
        if ($normalized === '') {
            return $normalized;
        }

        if (str_starts_with($normalized, 'act_')) {
            return $normalized;
        }

        $digits = preg_replace('/\D+/', '', $normalized);
        return $digits ? 'act_' . $digits : $normalized;
    }
}



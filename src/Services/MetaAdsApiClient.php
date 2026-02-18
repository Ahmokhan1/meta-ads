<?php

namespace T4E\MetaAds\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaAdsApiClient
{
    private array $config;

    public function __construct()
    {
        $this->config = config('meta_ads', []);
    }

    public function fetchAccount(string $adAccountId): array
    {
        $payload = $this->graphRequest('/' . $this->normalizeAccountId($adAccountId), [
            'fields' => 'id,name,currency,timezone_name,account_status',
        ]);

        return [
            'id' => (string) ($payload['id'] ?? ''),
            'name' => $payload['name'] ?? null,
            'currencyCode' => $payload['currency'] ?? null,
            'timeZone' => $payload['timezone_name'] ?? null,
            'status' => $this->mapAccountStatus($payload['account_status'] ?? null),
        ];
    }

    public function fetchCampaignDailyInsights(string $adAccountId, Carbon $from, Carbon $to): array
    {
        $accountId = $this->normalizeAccountId($adAccountId);
        $campaignMap = $this->fetchCampaignMap($accountId);

        $includeActions = (bool) ($this->config['include_actions'] ?? true);

        $fields = [
            'campaign_id',
            'campaign_name',
            'date_start',
            'date_stop',
            'impressions',
            'clicks',
            'reach',
            'spend',
            'ctr',
            'cpc',
            'cpm',
            'frequency',
        ];

        if ($includeActions) {
            $fields[] = 'actions';
            $fields[] = 'action_values';
        }

        $chunkDays = (int) ($this->config['range_chunk_days'] ?? 15);
        if ($chunkDays < 1) {
            $chunkDays = 15;
        }

        $result = [];
        $seen = [];

        foreach ($this->splitDateRange($from, $to, $chunkDays) as [$chunkFrom, $chunkTo]) {
            $rows = $this->graphPaginatedRequest('/' . $accountId . '/insights', [
                'level' => 'campaign',
                'time_increment' => 1,
                'fields' => implode(',', $fields),
                'time_range' => json_encode([
                    'since' => $chunkFrom->toDateString(),
                    'until' => $chunkTo->toDateString(),
                ]),
                'limit' => 500,
            ]);

            foreach ($rows as $row) {
                $campaignId = isset($row['campaign_id']) ? (string) $row['campaign_id'] : '';
                $date = $row['date_start'] ?? null;

                if ($campaignId === '' || !$date) {
                    continue;
                }

                $dedupeKey = $campaignId . '|' . $date;
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $campaignMeta = $campaignMap[$campaignId] ?? [];

                $actions = ($includeActions && is_array($row['actions'] ?? null)) ? $row['actions'] : [];
                $actionValues = ($includeActions && is_array($row['action_values'] ?? null)) ? $row['action_values'] : [];

                $leadActions = $includeActions
                    ? (int) round($this->sumActionValues($actions, [
                        'lead',
                        'onsite_conversion.lead_grouped',
                        'offsite_conversion.fb_pixel_lead',
                        'offsite_conversion.fb_pixel_complete_registration',
                        'offsite_conversion.fb_pixel_contact',
                        'offsite_conversion.custom',
                    ]))
                    : 0;

                $purchaseActions = $includeActions
                    ? (int) round($this->sumActionValues($actions, [
                        'purchase',
                        'omni_purchase',
                        'offsite_conversion.fb_pixel_purchase',
                    ]))
                    : 0;

                $purchaseValue = $includeActions
                    ? $this->sumActionValues($actionValues, [
                        'purchase',
                        'omni_purchase',
                        'offsite_conversion.fb_pixel_purchase',
                    ])
                    : 0.0;

                $result[] = [
                    'campaign' => [
                        'id' => $campaignId,
                        'name' => $row['campaign_name'] ?? ($campaignMeta['name'] ?? null),
                        'status' => $campaignMeta['status'] ?? null,
                        'objective' => $campaignMeta['objective'] ?? null,
                        'startDate' => $campaignMeta['start_date'] ?? null,
                        'endDate' => $campaignMeta['end_date'] ?? null,
                    ],
                    'metrics' => [
                        'impressions' => $this->toInt($row['impressions'] ?? 0),
                        'clicks' => $this->toInt($row['clicks'] ?? 0),
                        'reach' => $this->toInt($row['reach'] ?? 0),
                        'spend' => $this->toFloat($row['spend'] ?? 0),
                        'ctr' => $this->toFloat($row['ctr'] ?? 0),
                        'cpc' => $this->toFloat($row['cpc'] ?? 0),
                        'cpm' => $this->toFloat($row['cpm'] ?? 0),
                        'frequency' => $this->toFloat($row['frequency'] ?? 0),
                        'leads' => $leadActions,
                        'purchases' => $purchaseActions,
                        'purchaseValue' => $purchaseValue,
                    ],
                    'segments' => [
                        'date' => $date,
                    ],
                ];
            }
        }

        return $result;
    }

    public function sendConversions(string $pixelId, array $events, ?string $testEventCode = null): array
    {
        $this->guardConfig();

        $pixel = trim((string) $pixelId);
        if ($pixel === '') {
            throw new RuntimeException('Meta Ads config missing: pixel_id');
        }

        if (!$events) {
            return ['events_received' => 0];
        }

        $payload = [
            'data' => array_values($events),
            'access_token' => (string) ($this->config['access_token'] ?? ''),
        ];

        if ($testEventCode) {
            $payload['test_event_code'] = $testEventCode;
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/events',
            ltrim((string) ($this->config['api_version'] ?? 'v21.0'), '/'),
            ltrim($pixel, '/')
        );

        $response = $this->httpClient()->asJson()->post($url, $payload);

        return $this->parseResponse($response);
    }

    private function fetchCampaignMap(string $accountId): array
    {
        $rows = $this->graphPaginatedRequest('/' . $accountId . '/campaigns', [
            'fields' => 'id,name,status,effective_status,objective,start_time,stop_time',
            'limit' => 500,
        ]);

        $map = [];
        foreach ($rows as $row) {
            $id = isset($row['id']) ? (string) $row['id'] : '';
            if ($id === '') {
                continue;
            }

            $map[$id] = [
                'name' => $row['name'] ?? null,
                'status' => $row['effective_status'] ?? $row['status'] ?? null,
                'objective' => $row['objective'] ?? null,
                'start_date' => $this->toDateString($row['start_time'] ?? null),
                'end_date' => $this->toDateString($row['stop_time'] ?? null),
            ];
        }

        return $map;
    }

    private function graphPaginatedRequest(string $path, array $params = []): array
    {
        $payload = $this->graphRequest($path, $params);
        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $next = $payload['paging']['next'] ?? null;

        while (is_string($next) && $next !== '') {
            $payload = $this->graphNextRequest($next);
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            if ($data) {
                $rows = array_merge($rows, $data);
            }
            $next = $payload['paging']['next'] ?? null;
        }

        return $rows;
    }

    private function graphRequest(string $path, array $params = []): array
    {
        $this->guardConfig();

        $url = sprintf(
            'https://graph.facebook.com/%s/%s',
            ltrim((string) ($this->config['api_version'] ?? 'v21.0'), '/'),
            ltrim($path, '/')
        );

        $params['access_token'] = (string) ($this->config['access_token'] ?? '');

        $response = $this->httpClient()->get($url, $params);

        return $this->parseResponse($response);
    }

    private function graphNextRequest(string $url): array
    {
        $response = $this->httpClient()->get($url);

        return $this->parseResponse($response);
    }

    private function httpClient()
    {
        $timeout = (int) ($this->config['timeout'] ?? 180);
        if ($timeout < 30) {
            $timeout = 30;
        }

        $tries = (int) ($this->config['retries'] ?? 4);
        if ($tries < 0) {
            $tries = 0;
        }

        $baseSleepMs = (int) ($this->config['retry_sleep_ms'] ?? 800);
        if ($baseSleepMs < 0) {
            $baseSleepMs = 0;
        }

        return Http::timeout($timeout)
            ->acceptJson()
            ->retry(
                $tries,
                $baseSleepMs,
                function ($exception, Response $response = null) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($response) {
                        $status = $response->status();
                        if ($status === 429) return true;
                        if ($status >= 500 && $status <= 599) return true;
                    }

                    return false;
                }
            );
    }

    private function parseResponse(Response $response): array
    {
        $status = $response->status();
        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException('Meta Ads API returned an invalid response payload.');
        }

        if ($status < 200 || $status >= 300 || isset($payload['error'])) {
            $error = $payload['error'] ?? [];
            $message = $error['message'] ?? 'Meta Ads API request failed.';
            $code = $error['code'] ?? $status;
            $subcode = $error['error_subcode'] ?? null;

            $suffix = $subcode !== null ? ' (subcode: ' . $subcode . ')' : '';
            throw new RuntimeException('Meta Ads API error [' . $code . ']: ' . $message . $suffix);
        }

        return $payload;
    }

    private function splitDateRange(Carbon $from, Carbon $to, int $days = 15): array
    {
        $ranges = [];

        $start = $from->copy()->startOfDay();
        $endAll = $to->copy()->startOfDay();

        if ($start->gt($endAll)) {
            return [];
        }

        $cursor = $start->copy();
        while ($cursor->lte($endAll)) {
            $chunkEnd = $cursor->copy()->addDays($days - 1);
            if ($chunkEnd->gt($endAll)) {
                $chunkEnd = $endAll->copy();
            }
            $ranges[] = [$cursor->copy(), $chunkEnd->copy()];
            $cursor = $chunkEnd->copy()->addDay();
        }

        return $ranges;
    }

    private function sumActionValues(array $rows, array $types): float
    {
        $typesLower = array_map(fn ($t) => strtolower((string) $t), $types);

        $sum = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $actionType = strtolower((string) ($row['action_type'] ?? ''));
            if ($actionType === '' || !in_array($actionType, $typesLower, true)) {
                continue;
            }
            $sum += $this->toFloat($row['value'] ?? 0);
        }

        return $sum;
    }

    private function mapAccountStatus($status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        $map = [
            1 => 'ACTIVE',
            2 => 'DISABLED',
            3 => 'UNSETTLED',
            7 => 'PENDING_RISK_REVIEW',
            8 => 'PENDING_SETTLEMENT',
            9 => 'IN_GRACE_PERIOD',
            100 => 'PENDING_CLOSURE',
            101 => 'CLOSED',
        ];

        $num = is_numeric($status) ? (int) $status : null;
        if ($num !== null && isset($map[$num])) {
            return $map[$num];
        }

        return is_string($status) ? strtoupper(trim($status)) : (string) $status;
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

    private function toDateString($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toInt($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round((float) $value);
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function guardConfig(): void
    {
        if (empty($this->config['access_token'])) {
            throw new RuntimeException('Meta Ads config missing: access_token');
        }
    }
}

<?php

namespace Ahmokhan1\MetaAds\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Ahmokhan1\MetaAds\Services\MetaAdsApiClient;

class SendMetaAdsConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $leadId;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(int $leadId)
    {
        $this->leadId = $leadId;
    }

    public function handle(MetaAdsApiClient $client): void
    {
        $leadClass = config('meta_ads.lead_model');
        if (!$leadClass || !class_exists($leadClass)) {
            Log::warning('Meta Ads conversion skipped: lead_model not configured.');
            return;
        }

        $lead = $leadClass::find($this->leadId);
        if (!$lead) {
            return;
        }

        $allowLocal = (bool) config('meta_ads.allow_local', false);
        if (app()->environment('local') && !$allowLocal) {
            return;
        }

        if (!empty($lead->meta_conversion_sent_at)) {
            return;
        }

        $hasMetaSignals = (bool) ($lead->fbclid || $lead->fbc || $lead->fbp || $lead->meta_campaign_id || $lead->meta_ad_set_id || $lead->meta_ad_id);
        if (!$hasMetaSignals) {
            return;
        }

        $pixelId = (string) config('meta_ads.pixel_id');
        $accessToken = (string) config('meta_ads.access_token');
        if ($pixelId === '' || $accessToken === '') {
            $this->saveLeadError($lead, 'Missing Meta Ads configuration: access_token or pixel_id.');
            return;
        }

        $eventName = (string) config('meta_ads.event_name', 'Purchase');
        $actionSource = (string) config('meta_ads.action_source', 'website');
        $testEventCode = config('meta_ads.test_event_code');
        $currencyCode = (string) config('meta_ads.currency_code', 'GBP');
        $conversionValue = (float) config('meta_ads.conversion_value', 0);
        $orderId = 'lead-' . $lead->id;

        $frontOrder = $this->resolveOrder($lead);
        if ($frontOrder) {
            $totalField = (string) config('meta_ads.order_total_field', 'total_amount');
            $total = data_get($frontOrder, $totalField);
            if (is_numeric($total)) {
                $conversionValue = (float) $total;
                $orderId = 'order-' . $frontOrder->id;
            }
        }

        $conversionTime = !empty($lead->enrolment_date)
            ? Carbon::parse($lead->enrolment_date)->startOfDay()
            : (!empty($lead->created_at) ? Carbon::parse($lead->created_at) : now());

        $userData = $this->buildUserData($lead);
        if (!$userData) {
            $this->saveLeadError($lead, 'Missing Meta Ads user_data for conversion.');
            return;
        }

        $customData = [
            'currency' => $currencyCode,
            'value' => $conversionValue,
            'order_id' => $orderId,
        ];

        $courseName = data_get($lead, 'course_interested');
        if ($courseName) {
            $customData['content_name'] = $courseName;
        }

        $courseIds = data_get($lead, 'course_ids');
        if (is_string($courseIds)) {
            $decoded = json_decode($courseIds, true);
            if (is_array($decoded)) {
                $courseIds = $decoded;
            }
        }

        if (is_array($courseIds) && $courseIds) {
            $customData['content_ids'] = array_map('strval', $courseIds);
            $customData['content_type'] = 'product';
        }

        $event = [
            'event_name' => $eventName,
            'event_time' => $conversionTime->timestamp,
            'action_source' => $actionSource,
            'event_id' => 'lead-' . $lead->id,
            'user_data' => $userData,
            'custom_data' => $customData,
        ];

        try {
            $response = $client->sendConversions($pixelId, [$event], is_string($testEventCode) ? $testEventCode : null);
            $eventsReceived = (int) ($response['events_received'] ?? 0);

            if ($eventsReceived < 1) {
                $this->saveLeadError($lead, 'Meta Ads API accepted request but did not confirm events_received.');
                return;
            }

            $lead->meta_conversion_sent_at = now();
            $lead->meta_conversion_error = null;
            $lead->save();
        } catch (Throwable $e) {
            $this->saveLeadError($lead, $e->getMessage());

            Log::warning('Meta Ads conversion upload failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveOrder($lead)
    {
        $orderClass = config('meta_ads.order_model');
        if (!$orderClass || !class_exists($orderClass)) {
            return null;
        }

        $leadKey = (string) config('meta_ads.order_lead_key', 'lead_id');
        return $orderClass::query()
            ->where($leadKey, $lead->id)
            ->orderByDesc('id')
            ->first();
    }

    private function saveLeadError($lead, string $message): void
    {
        $lead->meta_conversion_error = $message;
        $lead->save();
    }

    private function buildUserData($lead): array
    {
        $userData = [];

        $fbc = data_get($lead, 'fbc');
        $fbclid = data_get($lead, 'fbclid');
        if (!empty($fbc)) {
            $userData['fbc'] = $fbc;
        } elseif (!empty($fbclid)) {
            $userData['fbc'] = 'fb.1.' . now()->timestamp . '.' . $fbclid;
        }

        $fbp = data_get($lead, 'fbp');
        if (!empty($fbp)) {
            $userData['fbp'] = $fbp;
        }

        $emailHash = $this->hashValue(data_get($lead, 'email'));
        if ($emailHash) {
            $userData['em'] = $emailHash;
        }

        $phoneHash = $this->hashPhone(data_get($lead, 'contact_number'));
        if ($phoneHash) {
            $userData['ph'] = $phoneHash;
        }

        [$firstName, $lastName] = $this->splitName(data_get($lead, 'candidate_name'));
        $firstHash = $this->hashValue($firstName);
        $lastHash = $this->hashValue($lastName);
        if ($firstHash) {
            $userData['fn'] = $firstHash;
        }
        if ($lastHash) {
            $userData['ln'] = $lastHash;
        }

        $userData['external_id'] = $this->hashValue((string) data_get($lead, 'id'));

        return array_filter($userData, static fn($value) => $value !== null && $value !== '');
    }

    private function hashValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim(mb_strtolower($value));
        if ($normalized === '') {
            return null;
        }

        return hash('sha256', $normalized);
    }

    private function hashPhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return hash('sha256', $digits);
    }

    private function splitName(?string $value): array
    {
        $name = trim((string) $value);
        if ($name === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+/', $name);
        if (!$parts) {
            return [null, null];
        }

        $first = $parts[0] ?? null;
        $last = null;
        if (count($parts) > 1) {
            $last = $parts[count($parts) - 1];
        }

        return [$first, $last];
    }
}



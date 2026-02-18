# Meta Ads (CAPI + Insights)

Laravel package for Meta Conversions API uploads and Marketing API insights sync.

## Install

```bash
composer require ahmokhan1/meta-ads
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=meta-ads-config
php artisan vendor:publish --tag=meta-ads-migrations
php artisan migrate
```

Fresh Laravel (no Lead/FrontOrder models yet):

```bash
php artisan vendor:publish --tag=meta-ads-models
php artisan vendor:publish --tag=meta-ads-demo-migrations
php artisan migrate
```

## Requirements

- Laravel 10, 11, or 12
- PHP 8.1+ (Laravel 12 requires PHP 8.2+)

## Environment

```dotenv
META_ADS_ACCESS_TOKEN=
META_ADS_AD_ACCOUNT_ID=
META_ADS_PIXEL_ID=
META_ADS_API_VERSION=v21.0
META_ADS_CURRENCY_CODE=GBP
META_ADS_EVENT_NAME=Purchase
META_ADS_ACTION_SOURCE=website
META_ADS_TEST_EVENT_CODE=
META_ADS_CONVERSION_VALUE=0
META_ADS_ALLOW_LOCAL=false
```

Optional model/table overrides:

```dotenv
META_ADS_LEAD_MODEL=App\Models\Lead
META_ADS_LEADS_TABLE=leads
```

## Required Lead/Order Columns

Leads table:
- `fbclid`, `fbc`, `fbp`
- `meta_campaign_id`, `meta_ad_set_id`, `meta_ad_id`
- `meta_conversion_sent_at`, `meta_conversion_error`

These are created by the published migrations.

## Conversions (CAPI)

Dispatch after a lead is enrolled/created:

```php
use Ahmokhan1\MetaAds\Jobs\SendMetaAdsConversionJob;

SendMetaAdsConversionJob::dispatch($lead->id)->delay(now()->addSeconds(5));
```

Behavior:
- Event id uses `lead-{id}`.
- `meta_conversion_sent_at` is set only when Meta responds with `events_received >= 1`.

Enable local testing with:

```dotenv
META_ADS_ALLOW_LOCAL=true
META_ADS_TEST_EVENT_CODE=TEST123
```

## Insights Sync

Sync daily campaign metrics:

```bash
php artisan meta-ads:sync --days=30
```

Use in scheduler:

```php
$schedule->command('meta-ads:sync --days=30')->dailyAt('01:20');
```

Tables:
- `meta_ads_accounts`
- `meta_ads_campaigns`
- `meta_ads_metrics_daily`

## Notes

- This package expects standard Meta UTMs/click ids to be captured in your lead/order records.
- Update `config/meta_ads.php` if your model/table names differ.

## License

MIT

<?php

return [
    'access_token' => env('META_ADS_ACCESS_TOKEN'),
    'ad_account_id' => env('META_ADS_AD_ACCOUNT_ID'),
    'api_version' => env('META_ADS_API_VERSION', 'v21.0'),
    'currency_code' => env('META_ADS_CURRENCY_CODE', 'GBP'),
    'crm_attribution_effective_from' => env('META_ADS_CRM_ATTRIBUTION_EFFECTIVE_FROM'),
    'timeout' => (int) env('META_ADS_TIMEOUT', 180),
    'connect_timeout' => env('META_ADS_CONNECT_TIMEOUT', 15),
    'retries' => env('META_ADS_RETRIES', 4),
    'retry_sleep_ms' => env('META_ADS_RETRY_SLEEP_MS', 800),
    'range_chunk_days' => env('META_ADS_RANGE_CHUNK_DAYS', 15),
    'include_actions' => env('META_ADS_INCLUDE_ACTIONS', true),

    'pixel_id' => env('META_ADS_PIXEL_ID'),
    'event_name' => env('META_ADS_EVENT_NAME', 'Purchase'),
    'action_source' => env('META_ADS_ACTION_SOURCE', 'website'),
    'test_event_code' => env('META_ADS_TEST_EVENT_CODE'),
    'conversion_value' => env('META_ADS_CONVERSION_VALUE', 0),
    'allow_local' => env('META_ADS_ALLOW_LOCAL', false),

    'lead_model' => env('META_ADS_LEAD_MODEL', App\Models\Lead::class),
    'order_model' => env('META_ADS_ORDER_MODEL', App\Models\FrontOrder::class),
    'leads_table' => env('META_ADS_LEADS_TABLE', 'leads'),
    'orders_table' => env('META_ADS_ORDERS_TABLE', 'front_orders'),
    'order_total_field' => env('META_ADS_ORDER_TOTAL_FIELD', 'total_amount'),
    'order_lead_key' => env('META_ADS_ORDER_LEAD_KEY', 'lead_id'),
];

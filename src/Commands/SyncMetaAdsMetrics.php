<?php

namespace Ahmokhan1\MetaAds\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;
use Ahmokhan1\MetaAds\Services\MetaAdsSyncService;

class SyncMetaAdsMetrics extends Command
{
    protected $signature = 'meta-ads:sync {--from=} {--to=} {--days=30}';
    protected $description = 'Sync Meta Ads campaign metrics into CRM.';

    public function handle(MetaAdsSyncService $service): int
    {
        try {
            [$from, $to] = $this->resolveRange();
            $updated = $service->syncMetrics($from, $to);
            $this->info('Synced ' . $updated . ' metric rows.');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Meta Ads sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolveRange(): array
    {
        $fromOpt = $this->option('from');
        $toOpt = $this->option('to');
        $days = (int) $this->option('days');

        if ($fromOpt || $toOpt) {
            $from = $fromOpt ? Carbon::parse($fromOpt) : now()->subDays($days ?: 30);
            $to = $toOpt ? Carbon::parse($toOpt) : now();
        } else {
            $days = $days > 0 ? $days : 30;
            $to = now();
            $from = now()->subDays($days - 1);
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }
}



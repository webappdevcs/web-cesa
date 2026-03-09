<?php

namespace Cesa\FormTransfer\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class WhatsAppThrottleService
{
    public function getDispatchDelaySeconds(): int
    {
        $config = config('form-transfer.notifications.whatsapp.throttle', []);

        if (! Arr::get($config, 'enabled', true)) {
            return 0;
        }

        $minIntervalSeconds = (int) Arr::get($config, 'min_interval_seconds', 0);

        if ($minIntervalSeconds <= 0) {
            return 0;
        }

        $maxIntervalSeconds = Arr::has($config, 'max_interval_seconds')
            ? (int) Arr::get($config, 'max_interval_seconds', 0)
            : $minIntervalSeconds;

        if ($maxIntervalSeconds <= 0) {
            $maxIntervalSeconds = $minIntervalSeconds;
        }

        if ($maxIntervalSeconds < $minIntervalSeconds) {
            $maxIntervalSeconds = $minIntervalSeconds;
        }

        $provider = strtolower(trim((string) config('form-transfer.notifications.whatsapp.provider', 'generic')));
        $key = (string) Arr::get($config, 'key', 'global');

        $stateKey = sprintf('form-transfer:whatsapp:throttle:%s:%s:next_at', $provider, $key);
        $lockKey = $stateKey.':lock';

        try {
            $lock = Cache::lock($lockKey, max(10, $maxIntervalSeconds * 5));
        } catch (\Throwable) {
            return 0;
        }

        try {
            return (int) $lock->block(3, function () use ($stateKey, $minIntervalSeconds, $maxIntervalSeconds): int {
                $nowTimestamp = now()->timestamp;
                $nextTimestamp = (int) Cache::get($stateKey, 0);
                $scheduledTimestamp = max($nowTimestamp, $nextTimestamp);

                $intervalSeconds = $minIntervalSeconds;

                if ($maxIntervalSeconds > $minIntervalSeconds) {
                    $intervalSeconds = random_int($minIntervalSeconds, $maxIntervalSeconds);
                }

                Cache::put($stateKey, $scheduledTimestamp + $intervalSeconds, 21600);

                return max(0, $scheduledTimestamp - $nowTimestamp);
            });
        } catch (LockTimeoutException) {
            return 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}

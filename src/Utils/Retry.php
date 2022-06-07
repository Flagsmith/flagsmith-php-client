<?php

namespace Flagsmith\Utils;

/**
 * This is an implementatin of the retry lib in python.
 * https://urllib3.readthedocs.io/en/latest/reference/urllib3.util.html#urllib3.util.Retry
 */
class Retry
{
    private int $total = 1;
    private int $attempts = 0;
    private float $backoffFactor = 0.1;
    private float $backoffMax = 15;
    private array $statusForcelist = [413, 429, 503];

    public function __construct(int $total)
    {
        $this->total = $total;
    }

    /**
     * Whether to retry or not.
     * @param int $statusCode
     * @return bool
     */
    public function isRetry(?int $statusCode): bool
    {
        if (
            !empty($this->statusForcelist) && $statusCode
            && in_array($statusCode, $this->statusForcelist)) {
            return true;
        }

        return $this->total > $this->attempts;
    }

    /**
     * Calculate sleep time for this try.
     * @return float
     */
    public function calculateSleepTime(): float
    {
        $holdTimeMS = $this->backoffFactor * 2 * $this->attempts;
        if ($holdTimeMS >= $this->backoffMax) {
            $holdTimeMS = $this->backoffMax;
        }

        return $holdTimeMS * 1000;
    }

    /**
     * Sleep for the seconds from calculateSleepTime().
     * @return void
     */
    public function waitWithBackoff()
    {
        $waitTime = $this->calculateSleepTime();
        if ($waitTime > 1000000) {
            sleep((int) ($waitTime / 1000000));
        } else {
            usleep($waitTime);
        }
    }

    /**
     * Incement the retry attempt counter.
     * @return void
     */
    public function retryAttempted()
    {
        $this->attempts++;
    }

    /**
     * Bool if there are any retries left.
     * @return bool
     */
    public function hasRetriesRemaining(): bool
    {
        return $this->attempts === $this->total;
    }
}

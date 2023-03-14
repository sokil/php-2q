<?php

declare(strict_types=1);

namespace Sokil\Cache\TwoQ;

/**
 * 2Q tests pages for their second reference time.
 *
 * The Q2 cache algorithm is a caching algorithm that aims to balance between frequently accessed
 * and infrequently accessed items in a cache. It works by dividing the cache into three buffers:
 * a frequently accessed buffer (in), a moderately accessed buffer (out), and an infrequently accessed buffer (main).
 * The "in" is the smallest buffer and contains the most frequently accessed items.
 * The "out" buffer is larger and contains items that are accessed less frequently than those in the "in"
 * but more frequently than those in the "main".
 * The "main" is the largest buffer and contains items that are rarely accessed.
 *
 * @link https://www.vldb.org/conf/1994/P439.PDF
 */
final class TwoQCache implements \Countable
{
    /**
     * Used to return correlated requests in short period of time.
     */
    private array $inQueue = [];

    /**
     * For keys evicted from "in" queue.
     * Form "out" queue value may be completely evicted, or if referenced move to "mail" queue.
     */
    private array $outQueue = [];

    /**
     * Keys that frequently referenced
     * Must be LRU queue
     */
    private array $mainQueue = [];


    /**
     * @param int $inQueueCapacity should be 25% of the page slot8
     * @param int $outQueueCapacity should hold identifiers for as many pages as would fit on 50% of the buffer
     * @param int $mainQueueCapacity used when items reads from "out" buffer
     */
    public function __construct(
        private readonly int $inQueueCapacity,
        private readonly int $outQueueCapacity,
        private readonly int $mainQueueCapacity,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->mainQueue[$key])) {
            $value = $this->mainQueue[$key];
            unset($this->mainQueue[$key]);
            $this->mainQueue = [$key => $value] + $this->mainQueue;
        } elseif (isset($this->outQueue[$key])) {
            $value = $this->outQueue[$key];

            // The "out" queue, by contrast, is used to detect pages that have high long-term access rates.
            unset($this->outQueue[$key]);

            if (count($this->mainQueue) === $this->mainQueueCapacity) {
                array_pop($this->mainQueue);
            }

            $this->mainQueue = [$key => $value] + $this->mainQueue;
        } elseif (isset($this->inQueue[$key])) {
            // If a page in "in" queue is accessed, it
            // isn’t promoted to "main" queue, because the second access may
            // simply be a correlated reference.
            $value = $this->inQueue[$key];
        } else {
            $value = $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        if (isset($this->mainQueue[$key])) {
            $this->mainQueue[$key] = $value;
            unset($this->mainQueue[$key]);

            $this->mainQueue = [$key => $value] + $this->mainQueue;
        } elseif (isset($this->outQueue[$key])) {
            $this->outQueue[$key] = $value;
            unset($this->outQueue[$key]);

            if (count($this->mainQueue) === $this->mainQueueCapacity) {
                array_pop($this->mainQueue);
            }

            $this->mainQueue = [$key => $value] + $this->mainQueue;
        } elseif (isset($this->inQueue[$key])) {
            // If a page in "in" queue is accessed, it
            // isn’t promoted to "main" queue, because the second access may
            // simply be a correlated reference.
            $this->inQueue[$key] = $value;
        }  else {
            if (count($this->inQueue) === $this->inQueueCapacity) {
                if (count($this->outQueue) === $this->outQueueCapacity) {
                    array_pop($this->outQueue);
                }

                $lastInQueueKey = array_key_last($this->inQueue);
                $this->outQueue = [$lastInQueueKey => $this->inQueue[$lastInQueueKey]] + $this->outQueue;
                unset($this->inQueue[$lastInQueueKey]);
            }

            $this->inQueue = [$key => $value] + $this->inQueue;
        }
    }

    public function count(): int
    {
        return count($this->inQueue) + count($this->outQueue) + count($this->mainQueue);
    }
}

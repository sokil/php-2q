<?php

declare(strict_types=1);

namespace Sokil\Cache\TwoQ;

use PHPUnit\Framework\TestCase;

class TwoQCacheTest extends TestCase
{
    public function testGetExistedFromInBuffer()
    {
        $cache = new TwoQCache(2, 4, 2);
        $cache->set("key", true);
        $this->assertTrue($cache->get("key"));
    }

    public function testGetNotExistedFromInBuffer()
    {
        $cache = new TwoQCache(2, 4, 2);
        $this->assertNull($cache->get("key"));
    }

    public function testSetOnlyOwerflowsOutBuffer()
    {
        $cache = new TwoQCache(2, 4, 2);

        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $item) {
            $cache->set((string)$item, true);
        }

        $this->assertCount(6, $cache);

        $this->assertNull($cache->get("1"));
        $this->assertNull($cache->get("2"));
        $this->assertNull($cache->get("3"));
    }

    public function testReadFromOutBufferMovesItemToMainBuffer()
    {
        $cache = new TwoQCache(2, 4, 2);

        $cache->set("1", true); // set "1" to in
        $cache->set("2", true); // set "2" to in
        $cache->set("3", true); // move "1" to "out", set "3" to "in"

        $cache->get("1"); // move "1" from "out" to "main" buffer

        $cache->set("4", true); // move "2" to "out", set "4" to "in"
        $cache->set("5", true); // move "3" to "out", set "5" to "in"
        $cache->set("6", true); // move "4" to "out", set "6" to "in"
        $cache->set("7", true); // move "5" to "out", set "7" to "in"

        $this->assertCount(7, $cache);

        $this->assertTrue($cache->get("1"));
    }

    public function testEvictFromMainBuffer()
    {
        $cache = new TwoQCache(2, 4, 2);

        $cache->set("1", true);
        $cache->set("2", true);
        $cache->set("3", true);
        $cache->set("4", true);
        $cache->set("5", true);
        $cache->set("6", true);

        $cache->get("1"); // move to "main" from "out"
        $cache->get("2"); // move to "main" from "out"

        $cache->set("7", true);

        $cache->get("3"); // move to "main" from "out", evict "1" from main

        $this->assertCount(6, $cache);

        $this->assertNull($cache->get("1"));
    }

    public function testSetToMainBufferWillReEnqueueKeyInMainBuffer()
    {
        $cache = new TwoQCache(1, 1, 1);

        $cache->set("1", true); // 1 -> "in"
        $cache->set("2", true); // 1 -> "out", 2 -> "in"
        $cache->get("1"); // 1 -> "main"
        $cache->set("3", true); // 3 -> "in", 2 -> "out"

        $cache->set("1", 42); // set in main

        $this->assertEquals(42, $cache->get("1"));
    }

    public function testSetToMainBufferWithEvictFromMainBuffer()
    {
        $cache = new TwoQCache(1, 1, 1);

        $cache->set("1", true); // 1 -> "in"
        $cache->set("2", true); // 1 -> "out", 2 -> "in"
        $cache->get("1"); // 1 -> "main"
        $cache->set("3", true); // 3 -> "in", 2 -> "out"
        $cache->get("2"); // 2 -> "main", 1 -> evicted from main

        $this->assertNull($cache->get("1"));
    }

    public function testSetToOutBuffer()
    {
        $cache = new TwoQCache(1, 1, 1);

        $cache->set("1", true); // 1 -> "in"
        $cache->set("2", true); // 1 -> "out", 2 -> "in"
        $cache->set("1", false); // 1 -> "main"

        $this->assertFalse($cache->get("1"));
    }
}

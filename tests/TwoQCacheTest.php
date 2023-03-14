<?php

declare(strict_types=1);

namespace Sokil\Cache\TwoQ;

use PHPUnit\Framework\TestCase;

class TwoQCacheTest extends TestCase
{
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
}

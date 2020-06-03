<?php

namespace Jenky\Elastify\Tests;

use Jenky\Elastify\Tests\UserIndex;

class IndexTest extends TestCase
{
    public function test_index_exists()
    {
        $this->assertTrue(UserIndex::indexExists());
    }
}

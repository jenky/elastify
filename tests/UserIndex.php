<?php

namespace Jenky\Elastify\Tests;

use Jenky\Elastify\Index;

class UserIndex extends Index
{
    public function searchableAs(): string
    {
        return '.users';
    }
}

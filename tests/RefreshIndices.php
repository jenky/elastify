<?php

namespace Jenky\Elastify\Tests;

use Jenky\Elastify\Facades\ES;

trait RefreshIndices
{
    /**
     * @before
     */
    public function cleanAllIndices()
    {
        ES::delete(['index' => '_all']);
    }
}

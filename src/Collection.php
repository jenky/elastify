<?php

namespace Jenky\Elastify;

use Illuminate\Support\Collection as BaseCollection;
use Jenky\Elastify\Concerns\InteractsWithResponse;
use Jenky\Elastify\Contracts\ResponseInterface;

class Collection extends BaseCollection implements ResponseInterface
{
    use InteractsWithResponse;

    /**
     * Create elasticsearch response instance.
     *
     * @param  array $response
     * @return void
     */
    public function __construct(array $response)
    {
        $this->response = $response;

        return parent::__construct($this->hits());
    }
}

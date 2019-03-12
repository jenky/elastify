<?php

namespace Jenky\Elastify;

use Illuminate\Pagination\LengthAwarePaginator;
use Jenky\Elastify\Concerns\InteractsWithResponse;
use Jenky\Elastify\Contracts\ResponseInterface;

class Paginator extends LengthAwarePaginator implements ResponseInterface
{
    use InteractsWithResponse;

    /**
     * Create new paginator instance.
     *
     * @return void
     */
    public function __construct($items, int $perPage = 10, $currentPage, array $options = [])
    {
        $this->response = $items;

        return parent::__construct(
            $this->hits(),
            $this->total(),
            $perPage,
            $currentPage,
            $options
        );
    }
}

<?php

namespace Jenky\Elastify\Concerns;

use Illuminate\Support\Arr;

trait InteractsWithAlias
{
    /**
     * @var array
     */
    protected static $aliases;

    /**
     * Get index aliases.
     *
     * @return array
     */
    public function getAliases($index = null): array
    {
        if (is_null(static::$aliases)) {
            $aliases = $this->getConnection()
                ->indices()
                ->getAliases(['index' => $index ?: $this->getIndex()]);

            static::$aliases = Arr::get($aliases, 'aliases', []);
        }

        return static::$aliases;
    }
}

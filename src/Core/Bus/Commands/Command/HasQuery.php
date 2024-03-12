<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Command;

use LaravelJsonApi\Contracts\Query\QueryParameters;

trait HasQuery
{
    /**
     * @var QueryParameters|null
     */
    private ?QueryParameters $queryParameters = null;

    /**
     * Set the query parameters that will be used when processing the result payload.
     *
     * @param QueryParameters|null $query
     * @return $this
     */
    public function withQuery(?QueryParameters $query): static
    {
        $copy = clone $this;
        $copy->queryParameters = $query;

        return $copy;
    }

    /**
     * @return QueryParameters|null
     */
    public function query(): ?QueryParameters
    {
        return $this->queryParameters;
    }
}

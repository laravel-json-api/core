<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\FetchMany;

use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Query\Input\QueryMany;

class FetchManyActionInput extends ActionInput
{
    /**
     * @var QueryMany|null
     */
    private ?QueryMany $query = null;

    /**
     * @return QueryMany
     */
    public function query(): QueryMany
    {
        if ($this->query) {
            return $this->query;
        }

        return $this->query = new QueryMany(
            $this->type,
            (array) $this->request->query(),
        );
    }
}

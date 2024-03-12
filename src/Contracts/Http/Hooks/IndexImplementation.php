<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Http\Hooks;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;

interface IndexImplementation
{
    /**
     * @param Request $request
     * @param QueryParameters $query
     * @return void
     */
    public function searching(Request $request, QueryParameters $query): void;

    /**
     * @param mixed $data
     * @param Request $request
     * @param QueryParameters $query
     * @return void
     */
    public function searched(mixed $data, Request $request, QueryParameters $query): void;
}

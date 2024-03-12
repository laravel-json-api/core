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

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;

interface ShowRelationshipImplementation
{
    /**
     * @param object $model
     * @param string $field
     * @param Request $request
     * @param QueryParameters $query
     * @return void
     * @throws HttpResponseException
     */
    public function readingRelationship(
        object $model,
        string $field,
        Request $request,
        QueryParameters $query,
    ): void;

    /**
     * @param object|null $model
     * @param string $field
     * @param mixed $related
     * @param Request $request
     * @param QueryParameters $query
     * @return void
     * @throws HttpResponseException
     */
    public function readRelationship(
        ?object $model,
        string $field,
        mixed $related,
        Request $request,
        QueryParameters $query,
    ): void;
}

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

interface UpdateRelationshipImplementation
{
    /**
     * @param object $model
     * @param string $fieldName
     * @param Request $request
     * @param QueryParameters $query
     * @return void
     * @throws HttpResponseException
     */
    public function updatingRelationship(
        object $model,
        string $fieldName,
        Request $request,
        QueryParameters $query,
    ): void;

    /**
     * @param object $model
     * @param string $fieldName
     * @param mixed $related
     * @param Request $request
     * @param QueryParameters $query
     * @return void
     * @throws HttpResponseException
     */
    public function updatedRelationship(
        object $model,
        string $fieldName,
        mixed $related,
        Request $request,
        QueryParameters $query,
    ): void;
}

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

interface DestroyImplementation
{
    /**
     * @param object $model
     * @param Request $request
     * @return void
     * @throws HttpResponseException
     */
    public function deleting(object $model, Request $request): void;

    /**
     * @param object $model
     * @param Request $request
     * @return void
     * @throws HttpResponseException
     */
    public function deleted(object $model, Request $request): void;
}

<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Auth;

interface Authorizer
{
    /**
     * Authorize the index controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function index($request): bool;

    /**
     * Authorize the store controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function store($request): bool;

    /**
     * Authorize the show controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @param object $model
     * @return bool
     */
    public function show($request, object $model): bool;

    /**
     * Authorize the update controller action.
     *
     * @param object $model
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function update($request, object $model): bool;

    /**
     * Authorize the destroy controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @param object $model
     * @return bool
     */
    public function destroy($request, object $model): bool;

    /**
     * Authorize the show-related and show-relationship controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool
     */
    public function showRelationship($request, object $model, string $fieldName): bool;

    /**
     * Authorize the update-relationship controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool
     */
    public function updateRelationship($request, object $model, string $fieldName): bool;

    /**
     * Authorize the attach-relationship controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool
     */
    public function attachRelationship($request, object $model, string $fieldName): bool;

    /**
     * Authorize the detach-relationship controller action.
     *
     * @param \Illuminate\Http\Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool
     */
    public function detachRelationship($request, object $model, string $fieldName): bool;
}

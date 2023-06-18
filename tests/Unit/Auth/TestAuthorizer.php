<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Auth;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;

class TestAuthorizer implements \LaravelJsonApi\Contracts\Auth\Authorizer
{
    /**
     * @inheritDoc
     */
    public function index(?Request $request, string $modelClass): bool
    {
        // TODO: Implement index() method.
    }

    /**
     * @inheritDoc
     */
    public function store(?Request $request, string $modelClass): bool
    {
        // TODO: Implement store() method.
    }

    /**
     * @inheritDoc
     */
    public function show(?Request $request, object $model): bool
    {
        // TODO: Implement show() method.
    }

    /**
     * @inheritDoc
     */
    public function update(Request $request, object $model): bool
    {
        // TODO: Implement update() method.
    }

    /**
     * @inheritDoc
     */
    public function destroy(Request $request, object $model): bool
    {
        // TODO: Implement destroy() method.
    }

    /**
     * @inheritDoc
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement showRelated() method.
    }

    /**
     * @inheritDoc
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement showRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement updateRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement attachRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement detachRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function failed(): ErrorList|Error
    {
        // TODO: Implement failed() method.
    }
}

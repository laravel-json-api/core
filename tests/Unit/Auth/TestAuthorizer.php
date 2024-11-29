<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Auth;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;

class TestAuthorizer implements Authorizer
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
    public function update(?Request $request, object $model): bool
    {
        // TODO: Implement update() method.
    }

    /**
     * @inheritDoc
     */
    public function destroy(?Request $request, object $model): bool
    {
        // TODO: Implement destroy() method.
    }

    /**
     * @inheritDoc
     */
    public function showRelated(?Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement showRelated() method.
    }

    /**
     * @inheritDoc
     */
    public function showRelationship(?Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement showRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function updateRelationship(?Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement updateRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function attachRelationship(?Request $request, object $model, string $fieldName): bool
    {
        // TODO: Implement attachRelationship() method.
    }

    /**
     * @inheritDoc
     */
    public function detachRelationship(?Request $request, object $model, string $fieldName): bool
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

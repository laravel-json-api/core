<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Auth;

use LaravelJsonApi\Contracts\Auth\Container as AuthorizerContainer;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory as ResourceAuthorizerFactoryContract;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Values\ResourceType;

final readonly class ResourceAuthorizerFactory implements ResourceAuthorizerFactoryContract
{
    /**
     * ResourceAuthorizerFactory constructor
     *
     * @param AuthorizerContainer $authorizerContainer
     * @param SchemaContainer $schemaContainer
     */
    public function __construct(
        private AuthorizerContainer $authorizerContainer,
        private SchemaContainer $schemaContainer,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function make(ResourceType|string $type): ResourceAuthorizer
    {
        return new ResourceAuthorizer(
            $this->authorizerContainer->authorizerFor($type),
            $this->schemaContainer->modelClassFor($type),
        );
    }
}

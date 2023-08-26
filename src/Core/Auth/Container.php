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

namespace LaravelJsonApi\Core\Auth;

use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as ContainerContract;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Support\ContainerResolver;
use LaravelJsonApi\Core\Values\ResourceType;

class Container implements ContainerContract
{
    /**
     * @var callable|null
     */
    private static $implementation;

    /**
     * @var callable
     */
    private $resolver;

    /**
     * Specify the callback to use to guess the authorizer class from the schema class.
     *
     * @param callable|null $resolver
     * @return void
     */
    public static function guessUsing(?callable $resolver): void
    {
        static::$implementation = $resolver;
    }

    /**
     * Get the resolver to use to guess authorizer names.
     *
     * @return callable
     */
    public static function resolver(): callable
    {
        if (static::$implementation) {
            return static::$implementation;
        }

        return static::$implementation = new AuthorizerResolver();
    }

    /**
     * Container constructor
     *
     * @param ContainerResolver $container
     * @param SchemaContainer $schemas
     * @param callable|null $resolver
     */
    public function __construct(
        private readonly ContainerResolver $container,
        private readonly SchemaContainer $schemas,
        callable $resolver = null
    ) {
        $this->resolver = $resolver ?? self::resolver();
    }

    /**
     * @inheritDoc
     */
    public function authorizerFor(string|ResourceType $type): Authorizer
    {
        $binding = ($this->resolver)($this->schemas->schemaClassFor($type));
        $authorizer = $this->container->instance()->make($binding);

        assert(
            $authorizer instanceof Authorizer,
            "Container binding '{$binding}' is not a JSON:API authorizer.",
        );

        return $authorizer;
    }
}

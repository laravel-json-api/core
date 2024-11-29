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
        ?callable $resolver = null
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

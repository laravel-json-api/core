<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Schema\StaticSchema;

use Generator;
use LaravelJsonApi\Contracts\Schema\StaticSchema\ServerConventions;
use LaravelJsonApi\Contracts\Schema\StaticSchema\StaticSchemaFactory as StaticSchemaFactoryContract;

final readonly class StaticSchemaFactory implements StaticSchemaFactoryContract
{
    /**
     * StaticSchemaFactory constructor.
     *
     * @param ServerConventions $conventions
     */
    public function __construct(private ServerConventions $conventions = new DefaultConventions())
    {
    }

    /**
     * @inheritDoc
     */
    public function make(iterable $schemas): Generator
    {
        foreach ($schemas as $schema) {
            yield new ThreadCachedStaticSchema(
                new ReflectionStaticSchema($schema, $this->conventions),
            );
        }
    }
}
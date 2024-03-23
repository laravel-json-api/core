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

use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Schema\StaticSchema\ServerConventions;
use LaravelJsonApi\Contracts\Schema\StaticSchema\StaticSchema;
use LaravelJsonApi\Core\Schema\Attributes\Model;
use LaravelJsonApi\Core\Schema\Attributes\ResourceClass;
use LaravelJsonApi\Core\Schema\Attributes\Type;
use ReflectionClass;
use RuntimeException;

final readonly class ReflectionStaticSchema implements StaticSchema
{
    /**
     * @var ReflectionClass
     */
    private ReflectionClass $reflection;

    /**
     * ReflectionStaticSchema constructor.
     *
     * @param class-string<Schema> $schema
     * @param ServerConventions $conventions
     */
    public function __construct(
        private string $schema,
        private ServerConventions $conventions,
    ) {
        $this->reflection = new ReflectionClass($this->schema);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaClass(): string
    {
        return $this->schema;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        $type = null;

        if ($attribute = $this->attribute(Type::class)) {
            $type = $attribute->type;
        }

        return $type ?? $this->conventions->getTypeFor($this->schema);
    }

    /**
     * @inheritDoc
     */
    public function getUriType(): string
    {
        $uri = null;

        if ($attribute = $this->attribute(Type::class)) {
            $uri = $attribute->uri;
        }

        return $uri ?? $this->conventions->getUriTypeFor(
            $this->getType(),
        );
    }

    /**
     * @inheritDoc
     */
    public function getModel(): string
    {
        if ($attribute = $this->attribute(Model::class)) {
            return $attribute->value;
        }

        throw new RuntimeException('Model attribute not found on schema: ' . $this->schema);
    }

    /**
     * @inheritDoc
     */
    public function getResourceClass(): string
    {
        if ($attribute = $this->attribute(ResourceClass::class)) {
            return $attribute->value;
        }

        return $this->conventions->getResourceClassFor($this->schema);
    }

    /**
     * @template TAttribute
     * @param class-string<TAttribute> $class
     * @return TAttribute|null
     */
    private function attribute(string $class): ?object
    {
        $attribute = $this->reflection->getAttributes($class)[0] ?? null;

        return $attribute?->newInstance();
    }
}
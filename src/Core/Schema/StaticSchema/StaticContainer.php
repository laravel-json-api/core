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
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Schema\StaticSchema\StaticContainer as StaticContainerContract;
use LaravelJsonApi\Contracts\Schema\StaticSchema\StaticSchema;
use LaravelJsonApi\Core\Values\ResourceType;
use RuntimeException;

final class StaticContainer implements StaticContainerContract
{
    /**
     * @var array<class-string<Schema>, StaticSchema>
     */
    private array $schemas = [];

    /**
     * @var array<non-empty-string, class-string<Schema>>
     */
    private array $types = [];

    /**
     * @var array<non-empty-string, non-empty-string>|null
     */
    private ?array $uriTypes = null;

    /**
     * StaticContainer constructor.
     *
     * @param iterable<StaticSchema> $schemas
     */
    public function __construct(iterable $schemas)
    {
        foreach ($schemas as $schema) {
            assert($schema instanceof StaticSchema);
            $class = $schema->getSchemaClass();
            $this->schemas[$class] = $schema;
            $this->types[$schema->getType()] = $class;
        }

        ksort($this->types);
    }

    /**
     * @inheritDoc
     */
    public function schemaFor(string|Schema $schema): StaticSchema
    {
        $schema = is_object($schema) ? $schema::class : $schema;

        return $this->schemas[$schema] ?? throw new RuntimeException('Schema does not exist: ' . $schema);
    }

    /**
     * @inheritDoc
     */
    public function schemaForType(ResourceType|string $type): StaticSchema
    {
        return $this->schemaFor(
            $this->schemaClassFor($type),
        );
    }

    /**
     * @inheritDoc
     */
    public function exists(ResourceType|string $type): bool
    {
        return isset($this->types[(string) $type]);
    }

    /**
     * @inheritDoc
     */
    public function schemaClassFor(ResourceType|string $type): string
    {
        return $this->types[(string) $type] ?? throw new RuntimeException('Unrecognised resource type: ' . $type);
    }

    /**
     * @inheritDoc
     */
    public function modelClassFor(ResourceType|string $type): string
    {
        $schema = $this->schemaFor(
            $this->schemaClassFor($type),
        );

        return $schema->getModel();
    }

    /**
     * @inheritDoc
     */
    public function typeForUri(string $uriType): ?ResourceType
    {
        if ($this->uriTypes === null) {
            $this->uriTypes = [];
            foreach ($this->schemas as $schema) {
                $this->uriTypes[$schema->getUriType()] = $schema->getType();
            }
        }

        $type = $this->uriTypes[$uriType] ?? null;

        if ($type !== null) {
            return new ResourceType($type);
        }

        throw new RuntimeException('Unrecognised URI type: ' . $uriType);
    }

    /**
     * @inheritDoc
     */
    public function types(): array
    {
        return array_keys($this->types);
    }

    /**
     * @return Generator<StaticSchema>
     */
    public function getIterator(): Generator
    {
        foreach ($this->schemas as $schema) {
            yield $schema;
        }
    }
}
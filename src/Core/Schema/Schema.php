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

namespace LaravelJsonApi\Core\Schema;

use Generator;
use Illuminate\Support\Collection;
use IteratorAggregate;
use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Contracts\Schema\Attribute;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Contracts\Schema\Query as QueryContract;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema as SchemaContract;
use LaravelJsonApi\Contracts\Schema\SchemaAware as SchemaAwareContract;
use LaravelJsonApi\Contracts\Schema\Sortable;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Contracts\Store\Repository;
use LaravelJsonApi\Core\Resources\ResourceResolver;
use LaravelJsonApi\Core\Support\Arr;
use LaravelJsonApi\Core\Support\Str;
use LogicException;
use Traversable;
use function array_keys;
use function iterator_to_array;
use function sprintf;

abstract class Schema implements SchemaContract, IteratorAggregate
{
    /**
     * @var Server
     */
    protected Server $server;

    /**
     * The resource type as it appears in URIs.
     *
     * @var string|null
     */
    protected static ?string $uriType = null;

    /**
     * The key name for the resource "id".
     *
     * @var string|null
     */
    protected ?string $idKeyName = null;

    /**
     * The maximum depth of include paths.
     *
     * @var int
     */
    protected int $maxDepth = 1;

    /**
     * Whether resources of this type have a self link.
     *
     * @var bool
     */
    protected bool $selfLink = true;

    /**
     * The query schema instance.
     *
     * @var QueryContract|null
     */
    protected QueryContract|null $query = null;

    /**
     * @var array|null
     */
    private ?array $fields = null;

    /**
     * @var array|null
     */
    private ?array $attributes = null;

    /**
     * @var array|null
     */
    private ?array $relations = null;

    /**
     * @var callable|null
     */
    private static $resourceTypeResolver;

    /**
     * @var callable|null
     */
    private static $resourceResolver;

    /**
     * Get the resource fields.
     *
     * @return iterable
     */
    abstract public function fields(): iterable;

    /**
     * Specify the callback to use to guess the resource type from the schema class.
     *
     * @param callable $resolver
     * @return void
     */
    public static function guessTypeUsing(callable $resolver): void
    {
        static::$resourceTypeResolver = $resolver;
    }

    /**
     * @inheritDoc
     */
    public static function type(): string
    {
        $resolver = static::$resourceTypeResolver ?: new TypeResolver();

        return $resolver(static::class);
    }

    /**
     * @inheritDoc
     */
    public static function model(): string
    {
        if (isset(static::$model)) {
            return static::$model;
        }

        throw new LogicException('The model class name must be set.');
    }

    /**
     * Specify the callback to use to guess the resource class from the schema class.
     *
     * @param callable $resolver
     * @return void
     */
    public static function guessResourceUsing(callable $resolver): void
    {
        static::$resourceResolver = $resolver;
    }

    /**
     * @inheritDoc
     */
    public static function resource(): string
    {
        $resolver = static::$resourceResolver ?: new ResourceResolver();

        return $resolver(static::class);
    }

    /**
     * @inheritDoc
     */
    public static function uriType(): string
    {
        if (static::$uriType) {
            return static::$uriType;
        }

        return static::$uriType = Str::dasherize(static::type());
    }

    /**
     * Schema constructor.
     *
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @inheritDoc
     */
    public function repository(): ?Repository
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->allFields();
    }

    /**
     * @inheritDoc
     */
    public function url($extra = [], bool $secure = null): string
    {
        $extra = Arr::wrap($extra);

        array_unshift($extra, $this->uriType());

        return $this->server->url($extra, $secure);
    }

    /**
     * @inheritDoc
     */
    public function hasSelfLink(): bool
    {
        return $this->selfLink;
    }

    /**
     * @inheritDoc
     */
    public function id(): ID
    {
        $field = $this->allFields()['id'] ?? null;

        if ($field instanceof ID) {
            return $field;
        }

        throw new LogicException('Expecting an id field to exist.');
    }

    /**
     * @inheritDoc
     */
    public function idKeyName(): ?string
    {
        if ($this->idKeyName) {
            return $this->idKeyName;
        }

        return $this->idKeyName = $this->id()->key();
    }

    /**
     * @inheritDoc
     */
    public function fieldNames(): array
    {
        return array_keys($this->allFields());
    }

    /**
     * @inheritDoc
     */
    public function isField(string $name): bool
    {
        return isset($this->allFields()[$name]);
    }

    /**
     * @inheritDoc
     */
    public function field(string $name): Field
    {
        if ($field = $this->allFields()[$name] ?? null) {
            return $field;
        }

        throw new LogicException(sprintf(
            'Field %s does not exist on resource schema %s.',
            $name,
            $this->type()
        ));
    }

    /**
     * @inheritDoc
     */
    public function attributes(): iterable
    {
        yield from $this->allAttributes();
    }

    /**
     * @inheritDoc
     */
    public function attribute(string $name): Attribute
    {
        if ($field = $this->allAttributes()[$name] ?? null) {
            return $field;
        }

        throw new LogicException(sprintf(
            'Attribute %s does not exist on resource schema %s.',
            $name,
            $this->type()
        ));
    }

    /**
     * @inheritDoc
     */
    public function isAttribute(string $name): bool
    {
        $field = $this->allAttributes()[$name] ?? null;

        return $field instanceof Attribute;
    }

    /**
     * @inheritDoc
     */
    public function relationships(): iterable
    {
        yield from $this->allRelations();
    }

    /**
     * @inheritDoc
     */
    public function relationship(string $name): Relation
    {
        if ($field = $this->allRelations()[$name] ?? null) {
            return $field;
        }

        throw new LogicException(sprintf(
            'Relationship %s does not exist on resource schema %s.',
            $name,
            $this->type()
        ));
    }

    /**
     * @inheritDoc
     */
    public function relationshipForUri(string $uriFieldName): ?Relation
    {
        foreach ($this->relationships() as $relation) {
            if ($relation->uriName() === $uriFieldName) {
                return $relation;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function isRelationship(string $name): bool
    {
        $field = $this->allRelations()[$name] ?? null;

        return $field instanceof Relation;
    }

    /**
     * @inheritDoc
     */
    public function query(): QueryContract
    {
        if ($this->query) {
            return $this->query;
        }

        $queryClass = QueryResolver::getInstance()($this::class);

        return $this->query = new $queryClass($this);
    }

    /**
     * @inheritDoc
     */
    public function includePaths(): iterable
    {
        if (0 < $this->maxDepth) {
            return new IncludePathIterator(
                $this->server->schemas(),
                $this,
                $this->maxDepth
            );
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function pagination(): ?Paginator
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function isFilter(string $name): bool
    {
        /** @var Filter $filter */
        foreach ($this->filters() as $filter) {
            if ($filter->key() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isSparseField(string $fieldName): bool
    {
        foreach ($this->sparseFields() as $sparseField) {
            if ($sparseField === $fieldName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function sparseFields(): iterable
    {
        /** @var Field $field */
        foreach ($this as $field) {
            if ($field->isSparseField()) {
                yield $field->name();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function isSortField(string $name): bool
    {
        foreach ($this->sortFields() as $sortable) {
            if ($sortable === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function sortFields(): iterable
    {
        return array_keys(iterator_to_array(
            $this->allSortFields()
        ));
    }

    /**
     * @inheritDoc
     */
    public function sortField(string $name)
    {
        foreach ($this->allSortFields() as $key => $sortable) {
            if ($name === $key) {
                return $sortable;
            }
        }

        throw new LogicException(sprintf(
            'Sort field %s does not exist on schema %s.',
            $name,
            get_class($this),
        ));
    }

    /**
     * @inheritDoc
     */
    public function sortables(): iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function authorizable(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    private function allFields(): array
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        $fields = [];

        /** @var Field $field */
        foreach ($this->fields() as $field) {
            if ($field instanceof SchemaAwareContract) {
                $field->withSchemas($this->server->schemas());
            }

            $fields[$field->name()] = $field;
        }

        return $this->fields = $fields;
    }

    /**
     * @return array
     */
    private function allAttributes(): array
    {
        if (is_array($this->attributes)) {
            return $this->attributes;
        }

        return $this->attributes = Collection::make($this->allFields())
            ->whereInstanceOf(Attribute::class)
            ->all();
    }

    /**
     * @return array
     */
    private function allRelations(): array
    {
        if (is_array($this->relations)) {
            return $this->relations;
        }

        return $this->relations = Collection::make($this->allFields())
            ->whereInstanceOf(Relation::class)
            ->all();
    }

    /**
     * Iterate through all the sort fields.
     *
     * @return Generator<string,ID|Attribute|Sortable>
     */
    private function allSortFields(): Generator
    {
        $id = $this->id();

        if ($id->isSortable()) {
            yield $id->name() => $id;
        }

        /** @var Attribute $attribute */
        foreach ($this->attributes() as $attribute) {
            if ($attribute->isSortable()) {
                yield $attribute->name() => $attribute;
            }
        }

        /** @var Sortable $sortable */
        foreach ($this->sortables() as $sortable) {
            yield $sortable->sortField() => $sortable;
        }
    }
}

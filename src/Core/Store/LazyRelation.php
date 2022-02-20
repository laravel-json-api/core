<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Store;

use Illuminate\Support\Collection;
use IteratorAggregate;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Support\Arr;
use LogicException;
use Traversable;

class LazyRelation implements IteratorAggregate
{
    /**
     * @var Server
     */
    private Server $server;

    /**
     * @var Relation
     */
    protected Relation $relation;

    /**
     * @var array
     */
    private array $json;

    /**
     * @var Collection|null
     */
    private ?Collection $resources = null;

    /**
     * RelatedResource constructor.
     *
     * @param Server $server
     * @param Relation $relation
     * @param array $json
     */
    public function __construct(Server $server, Relation $relation, array $json)
    {
        $this->server = $server;
        $this->relation = $relation;
        $this->json = $json;
    }

    /**
     * Retrieve the related resource for a to-one relation.
     *
     * @return object|null
     */
    public function get(): ?object
    {
        if ($this->relation->toOne()) {
            return $this->toOne();
        }

        throw new LogicException('Can only retrieve the result of a to-one relation.');
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        if ($this->relation->toMany()) {
            yield from $this->toMany();
            return;
        }

        throw new LogicException('Can only iterate over a to-many relation.');
    }

    /**
     * Retrieve the related resources for a to-many relation.
     *
     * @return Collection
     */
    public function collect(): Collection
    {
        if ($this->relation->toMany()) {
            return $this->toMany();
        }

        throw new LogicException('Can only convert a to-many relation to a collection.');
    }

    /**
     * Retrieve the related resources for a to-many relation.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->toMany()->all();
    }

    /**
     * Retrieve the related resource for a to-one relation.
     *
     * @return object|null
     */
    private function toOne(): ?object
    {
        $data = $this->json['data'] ?? [];

        if ($this->isValid($data)) {
            return $this->server->store()->find(
                $data['type'],
                $data['id']
            );
        }

        return null;
    }

    /**
     * @return Collection
     */
    private function toMany(): Collection
    {
        if ($this->resources) {
            return $this->resources;
        }

        $data = $this->json['data'] ?? [];
        $identifiers = [];

        if (is_array($data) && !Arr::isAssoc($data)) {
            $identifiers = Collection::make($data)
                ->filter(fn($value): bool => $this->isValid($value))
                ->values()
                ->all();
        }

        if (empty($identifiers)) {
            return $this->resources = new Collection();
        }

        return $this->resources = Collection::make(
            $this->server->store()->findMany($identifiers)
        );
    }

    /**
     * @param mixed $identifier
     * @return bool
     */
    private function isValid($identifier): bool
    {
        if (is_array($identifier) && isset($identifier['type']) && isset($identifier['id'])) {
            return $this->isType($identifier['type']) && $this->isId($identifier['id']);
        }

        return false;
    }

    /**
     * @param mixed $type
     * @return bool
     */
    private function isType($type): bool
    {
        return in_array($type, $this->relation->allInverse(), true);
    }

    /**
     * @param mixed $id
     * @return bool
     */
    private function isId($id): bool
    {
        if (is_string($id)) {
            return !empty($id) || '0' === $id;
        }

        return false;
    }
}

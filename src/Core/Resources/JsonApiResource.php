<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Resources;

use ArrayAccess;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Resources\JsonApiRelation;
use LaravelJsonApi\Contracts\Resources\Serializer\Attribute as SerializableAttribute;
use LaravelJsonApi\Contracts\Resources\Serializer\Relation as SerializableRelation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\LinkHref;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Document\ResourceIdentifier;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Core\Resources\Concerns\ConditionallyLoadsAttributes;
use LaravelJsonApi\Core\Resources\Concerns\DelegatesToResource;
use LaravelJsonApi\Core\Responses\ResourceResponse;
use LogicException;
use function sprintf;

class JsonApiResource implements ArrayAccess, Responsable
{

    use ConditionallyLoadsAttributes;
    use DelegatesToResource;

    /**
     * The model that the resource represents.
     *
     * @var object
     */
    public object $resource;

    /**
     * @var Schema
     */
    protected Schema $schema;

    /**
     * The resource type.
     *
     * @var string
     */
    protected string $type = '';

    /**
     * @var string|null
     */
    protected ?string $selfUri = null;

    /**
     * @var array
     */
    private static array $types = [];

    /**
     * JsonApiResource constructor.
     *
     * @param Schema $schema
     * @param object $resource
     */
    public function __construct(Schema $schema, object $resource)
    {
        $this->schema = $schema;
        $this->resource = $resource;
    }

    /**
     * Get the resource's `self` link URL.
     *
     * @return string
     */
    public function selfUrl(): string
    {
        if ($this->selfUri) {
            return $this->selfUri;
        }

        return $this->selfUri = JsonApi::server()->url([
            $this->type(),
            $this->id(),
        ]);
    }

    /**
     * Get the `self` link for the resource.
     *
     * @return Link
     */
    public function selfLink(): Link
    {
        return new Link(
            'self',
            new LinkHref($this->selfUrl()),
            $this->selfMeta()
        );
    }

    /**
     * Get the resource's type.
     *
     * @return string
     */
    public function type(): string
    {
        if (!empty($this->type)) {
            return $this->type;
        }

        return $this->type = $this->schema->type();
    }

    /**
     * Get the resource's id.
     *
     * @return string
     */
    public function id(): string
    {
        if ($key = $this->schema->idKeyName()) {
            return (string) $this->resource->{$key};
        }

        if ($this->resource instanceof UrlRoutable) {
            return (string) $this->resource->getRouteKey();
        }

        throw new LogicException('Resource is not URL routable: you must implement the id method yourself.');
    }

    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        foreach ($this->schema->attributes() as $attr) {
            if ($attr instanceof SerializableAttribute && $attr->isNotHidden($request)) {
                yield $attr->serializedFieldName() => $attr->serialize($this->resource);
            }
        }
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        foreach ($this->schema->relationships() as $relation) {
            if ($relation instanceof SerializableRelation && $relation->isNotHidden($request)) {
                yield $relation->serializedFieldName() => $relation->serialize(
                    $this->resource,
                    $this->selfUrl(),
                );
            }
        }
    }

    /**
     * Was the resource created in the current HTTP request?
     *
     * @return bool
     */
    public function wasCreated(): bool
    {
        if (property_exists($this->resource, 'wasRecentlyCreated')) {
            return $this->resource->wasRecentlyCreated;
        }

        return false;
    }

    /**
     * Get the resource identifier for this resource.
     *
     * @return ResourceIdentifier
     */
    public function identifier(): ResourceIdentifier
    {
        return ResourceIdentifier::make($this->type(), $this->id())
            ->setMeta($this->identifierMeta());
    }

    /**
     * Get the resource's meta.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function meta($request): iterable
    {
        return [];
    }

    /**
     * Get the resource's links.
     *
     * @param Request|null $request
     * @return Links
     */
    public function links($request): Links
    {
        return new Links($this->selfLink());
    }

    /**
     * Get a resource relation by name.
     *
     * @param string $name
     * @return JsonApiRelation
     */
    public function relationship(string $name): JsonApiRelation
    {
        /** @var JsonApiRelation $relation */
        foreach ($this->relationships(null) as $relation) {
            if ($relation->fieldName() === $name) {
                return $relation;
            }
        }

        throw new LogicException(sprintf(
            'Unexpected relationship %s on resource %s.',
            $name,
            $this->type()
        ));
    }

    /**
     * Prepare the resource to become an HTTP response.
     *
     * @param Request $request
     * @return ResourceResponse
     */
    public function prepareResponse($request): ResourceResponse
    {
        return new ResourceResponse($this);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return $this->prepareResponse($request)->toResponse($request);
    }

    /**
     * Get meta for the `self` link.
     *
     * @return array
     */
    protected function selfMeta(): array
    {
        return [];
    }

    /**
     * Get meta for the resource's identifier.
     *
     * @return array
     */
    protected function identifierMeta(): array
    {
        return [];
    }

    /**
     * Create a new resource relation.
     *
     * @param string $fieldName
     * @param string|null $keyName
     * @return Relation
     */
    protected function relation(string $fieldName, string $keyName = null): Relation
    {
        return new Relation(
            $this->resource,
            $this->selfUrl(),
            $fieldName,
            $keyName
        );
    }

}

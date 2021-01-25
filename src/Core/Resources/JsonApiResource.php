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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\LinkHref;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Document\ResourceIdentifier;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Core\Resources\Concerns\ConditionallyLoadsAttributes;
use LaravelJsonApi\Core\Resources\Concerns\DelegatesToResource;
use LaravelJsonApi\Core\Responses\ResourceResponse;
use LaravelJsonApi\Core\Support\Str;
use LogicException;
use function sprintf;

abstract class JsonApiResource implements ArrayAccess, Responsable
{

    use ConditionallyLoadsAttributes;
    use DelegatesToResource;

    /**
     * The resource.
     *
     * @var Model|object
     */
    public object $resource;

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
     * @param Model|object $resource
     */
    public function __construct(object $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get the resource's attributes.
     *
     * @return iterable
     */
    abstract public function attributes(): iterable;

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

        return $this->type = $this->guessType();
    }

    /**
     * Get the resource's id.
     *
     * @return string
     */
    public function id(): string
    {
        if ($this->resource instanceof UrlRoutable) {
            return $this->resource->getRouteKey();
        }

        throw new LogicException('Resource is not URL routable: you must implement the id method yourself.');
    }

    /**
     * Get the resource's relationships.
     *
     * @return iterable
     */
    public function relationships(): iterable
    {
        return [];
    }

    /**
     * Was the resource created in the current HTTP request?
     *
     * @return bool
     */
    public function wasCreated(): bool
    {
        if ($this->resource instanceof Model) {
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
     * @return array
     */
    public function meta(): array
    {
        return [];
    }

    /**
     * Get the resource's links.
     *
     * @return Links
     */
    public function links(): Links
    {
        return new Links($this->selfLink());
    }

    /**
     * Get a resource relation by name.
     *
     * @param string $name
     * @return Relation
     */
    public function relationship(string $name): Relation
    {
        /** @var Relation $relation */
        foreach ($this->relationships() as $relation) {
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
        return new Relation($this, $fieldName, $keyName);
    }

    /**
     * Guess the resource's type.
     *
     * @return string
     */
    private static function guessType(): string
    {
        $fqn = static::class;

        if (isset(static::$types[$fqn])) {
            return static::$types[$fqn];
        }

        return static::$types[$fqn] = Str::dasherize(Str::plural(
            Str::before(class_basename($fqn), 'Resource')
        ));
    }
}

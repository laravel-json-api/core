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

declare(strict_types=1);

namespace LaravelJsonApi\Core\Resources;

use Countable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use IteratorAggregate;
use LaravelJsonApi\Contracts\Pagination\Page as PageContract;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Pagination\Page;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Responses\Internal\PaginatedResourceResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceCollectionResponse;
use Traversable;
use function count;

class ResourceCollection implements Responsable, IteratorAggregate, Countable
{

    /**
     * @var iterable|PageContract
     */
    public iterable $resources;

    /**
     * @var bool
     */
    protected bool $preserveAllQueryParameters = false;

    /**
     * @var QueryParameters|null
     */
    protected ?QueryParameters $queryParameters = null;

    /**
     * ResourceCollection constructor.
     *
     * @param iterable $resources
     */
    public function __construct(iterable $resources)
    {
        $this->resources = $resources;
    }

    /**
     * Indicate that all current query parameters should be appended to pagination links.
     *
     * @return $this
     */
    public function preserveQuery(): self
    {
        $this->preserveAllQueryParameters = true;

        return $this;
    }

    /**
     * Specify the query string parameters that should be present on pagination links.
     *
     * @param mixed $query
     * @return $this
     */
    public function withQuery($query): self
    {
        $this->preserveAllQueryParameters = false;
        $this->queryParameters = QueryParameters::cast($query);

        return $this;
    }

    /**
     * @return array
     */
    public function meta(): array
    {
        return [];
    }

    /**
     * @return Links
     */
    public function links(): Links
    {
        return new Links();
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->resources;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->resources);
    }

    /**
     * @return bool
     */
    public function isPaginated(): bool
    {
        if ($this->resources instanceof PageContract) {
            return true;
        }

        return $this->resources instanceof Paginator;
    }

    /**
     * @param Request $request
     * @return ResourceCollectionResponse
     */
    public function prepareResponse($request): ResourceCollectionResponse
    {
        if ($this->isPaginated()) {
            return $this->preparePaginationResponse($request);
        }

        return new ResourceCollectionResponse($this);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return $this->prepareResponse($request)->toResponse($request);
    }

    /**
     * @param Request $request
     * @return ResourceCollectionResponse
     */
    protected function preparePaginationResponse($request): ResourceCollectionResponse
    {
        /** Ensure the resources are a JSON:API page. */
        $this->resources = Page::cast($this->resources);

        if ($this->preserveAllQueryParameters) {
            $this->resources->withQuery($request->query());
        } else if ($this->queryParameters) {
            $this->resources->withQuery($this->queryParameters);
        }

        return new PaginatedResourceResponse($this);
    }

}

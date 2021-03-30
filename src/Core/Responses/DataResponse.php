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

namespace LaravelJsonApi\Core\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Resources\ResourceCollection;
use LaravelJsonApi\Core\Responses\Internal\PaginatedResourceResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceCollectionResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceResponse;
use function is_null;

class DataResponse implements Responsable
{

    use Concerns\IsResponsable;

    /**
     * @var Page|object|iterable|null
     */
    private $value;

    /**
     * @var bool|null
     */
    private ?bool $created = null;

    /**
     * Fluent constructor.
     *
     * @param Page|object|iterable|null $value
     * @return DataResponse
     */
    public static function make($value): self
    {
        return new self($value);
    }

    /**
     * DataResponse constructor.
     *
     * @param Page|object|iterable|null $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Mark the resource as created.
     *
     * @return $this
     */
    public function didCreate(): self
    {
        $this->created = true;

        return $this;
    }

    /**
     * Mark the resource as not created.
     *
     * @return $this
     */
    public function didntCreate(): self
    {
        $this->created = false;

        return $this;
    }

    /**
     * @param Request $request
     * @return ResourceCollectionResponse|ResourceResponse
     */
    public function prepareResponse($request): Responsable
    {
        return $this
            ->prepareDataResponse($request)
            ->withServer($this->server)
            ->withJsonApi($this->jsonApi())
            ->withMeta($this->meta)
            ->withLinks($this->links)
            ->withEncodeOptions($this->encodeOptions)
            ->withHeaders($this->headers());
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return $this
            ->prepareResponse($request)
            ->toResponse($request);
    }

    /**
     * Convert the data member to a response class.
     *
     * @param $request
     * @return PaginatedResourceResponse|ResourceCollectionResponse|ResourceResponse
     */
    private function prepareDataResponse($request)
    {
        if ($this->value instanceof Page) {
            return new PaginatedResourceResponse($this->value);
        }

        if (is_null($this->value)) {
            return new ResourceResponse(null);
        }

        if ($this->value instanceof JsonApiResource) {
            return $this->value->prepareResponse($request);
        }

        $resources = $this->server()->resources();

        if (is_object($this->value) && $resources->exists($this->value)) {
            return $resources
                ->create($this->value)
                ->prepareResponse($request)
                ->withCreated($this->created);
        }

        return (new ResourceCollection($this->value))->prepareResponse($request);
    }

}

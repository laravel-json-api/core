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
use function is_null;

class DataResponse implements Responsable
{

    use Concerns\IsResponsable;

    /**
     * @var Page|object|iterable|null
     */
    private $value;

    /**
     * @var bool
     */
    private bool $created = false;

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
     * @return $this
     */
    public function didCreate(): self
    {
        $this->created = true;

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

        $parsed = $this
            ->server()
            ->resources()
            ->resolve($this->value);

        if ($parsed instanceof JsonApiResource) {
            $response = $parsed->prepareResponse($request);
            return $this->created ? $response->didCreate() : $response;
        }

        return (new ResourceCollection($parsed))->prepareResponse($request);
    }

}

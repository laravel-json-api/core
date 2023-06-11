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

namespace LaravelJsonApi\Core\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Resources\ResourceCollection;
use LaravelJsonApi\Core\Responses\Concerns\HasEncodingParameters;
use LaravelJsonApi\Core\Responses\Concerns\IsResponsable;
use LaravelJsonApi\Core\Responses\Internal\PaginatedResourceResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceCollectionResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceResponse;

class DataResponse implements Responsable
{
    use HasEncodingParameters;
    use IsResponsable;

    /**
     * @var bool|null
     */
    public ?bool $created = null;

    /**
     * Fluent constructor.
     *
     * @param mixed|null $data
     * @return DataResponse
     */
    public static function make(mixed $data): self
    {
        return new self($data);
    }

    /**
     * DataResponse constructor.
     *
     * @param mixed|null $data
     */
    public function __construct(public readonly mixed $data)
    {
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
     * @return Responsable
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
            ->withIncludePaths($this->includePaths)
            ->withSparseFieldSets($this->fieldSets)
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
    private function prepareDataResponse($request):
    PaginatedResourceResponse|ResourceCollectionResponse|ResourceResponse
    {
        if ($this->data instanceof Page) {
            return new PaginatedResourceResponse($this->data);
        }

        if ($this->data === null) {
            return new ResourceResponse(null);
        }

        if ($this->data instanceof JsonApiResource) {
            return $this->data
                ->prepareResponse($request)
                ->withCreated($this->created);
        }

        $resources = $this->server()->resources();

        if (is_object($this->data) && $resources->exists($this->data)) {
            return $resources
                ->create($this->data)
                ->prepareResponse($request)
                ->withCreated($this->created);
        }

        if (is_iterable($this->data)) {
            return (new ResourceCollection($this->data))->prepareResponse($request);
        }

        throw new \LogicException('Unexpected data response value.');
    }

}

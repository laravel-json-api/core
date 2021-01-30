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
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ResourceResponse implements Responsable
{

    use Concerns\IsResponsable;

    /**
     * @var JsonApiResource|null
     */
    private ?JsonApiResource $resource;

    /**
     * @var bool
     */
    private bool $created = false;

    /**
     * ResourceResponse constructor.
     *
     * @param JsonApiResource|null $resource
     */
    public function __construct(?JsonApiResource $resource)
    {
        $this->resource = $resource;
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
     * @param Request $request
     * @return Response
     */
    public function toResponse($request)
    {
        $links = $this->links();

        if ($this->resource) {
            $links->push($this->resource->selfLink());
        }

        $document = JsonApi::server()->encoder()
            ->withRequest($request)
            ->withIncludePaths($this->includePaths($request))
            ->withFieldSets($this->fieldSets($request))
            ->withResource($this->resource)
            ->withJsonApi($this->jsonApi())
            ->withMeta($this->meta)
            ->withLinks($links)
            ->toJson($this->encodeOptions);

        return new Response(
            $document,
            $this->status(),
            $this->headers()
        );
    }

    /**
     * @return array
     */
    protected function headers(): array
    {
        $headers = \collect(['Content-Type' => 'application/vnd.api+json'])
            ->merge($this->headers ?: [])
            ->all();

        if ($this->resourceWasCreated()) {
            $headers['Location'] = $this->resource->selfUrl();
        }

        return $headers;
    }

    /**
     * @return int
     */
    protected function status(): int
    {
        if ($this->resourceWasCreated()) {
            return Response::HTTP_CREATED;
        }

        return Response::HTTP_OK;
    }

    /**
     * @return bool
     */
    protected function resourceWasCreated(): bool
    {
        if (true === $this->created) {
            return true;
        }

        if ($this->resource) {
            return $this->resource->wasCreated();
        }

        return false;
    }

}

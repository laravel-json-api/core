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
use LaravelJsonApi\Core\Responses\Concerns\HasEncodingParameters;
use LaravelJsonApi\Core\Responses\Concerns\HasRelationshipMeta;
use LaravelJsonApi\Core\Responses\Concerns\IsResponsable;
use LaravelJsonApi\Core\Responses\Internal\PaginatedIdentifierResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceCollectionResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceIdentifierCollectionResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceIdentifierResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceResponse;
use function is_null;

class RelationshipResponse implements Responsable
{

    use HasEncodingParameters;
    use HasRelationshipMeta;
    use IsResponsable;

    /**
     * @var object
     */
    private object $resource;

    /**
     * @var string
     */
    private string $fieldName;

    /**
     * @var Page|iterable|null
     */
    private $related;

    /**
     * Fluent constructor.
     *
     * @param object $resource
     * @param string $fieldName
     * @param Page|iterable|null $related
     * @return static
     */
    public static function make(object $resource, string $fieldName, $related): self
    {
        return new self($resource, $fieldName, $related);
    }

    /**
     * RelationshipResponse constructor.
     *
     * @param object $resource
     * @param string $fieldName
     * @param Page|iterable|null $related
     */
    public function __construct(object $resource, string $fieldName, $related)
    {
        $this->resource = $resource;
        $this->fieldName = $fieldName;
        $this->related = $related;
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
            ->withRelationshipMeta($this->hasRelationMeta)
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
     * @return ResourceIdentifierResponse|ResourceIdentifierCollectionResponse|PaginatedIdentifierResponse
     */
    private function prepareDataResponse($request)
    {
        $resources = $this->server()->resources();
        $resource = $resources->cast($this->resource);

        if (is_null($this->related)) {
            return new ResourceIdentifierResponse(
                $resource,
                $this->fieldName,
                null,
            );
        }

        if ($this->related instanceof Page) {
            return new PaginatedIdentifierResponse(
                $resource,
                $this->fieldName,
                $this->related,
            );
        }

        if (is_object($this->related) && $resources->exists($this->related)) {
            return new ResourceIdentifierResponse(
                $resource,
                $this->fieldName,
                $this->related,
            );
        }

        return new ResourceIdentifierCollectionResponse(
            $resource,
            $this->fieldName,
            $this->related,
        );
    }

}

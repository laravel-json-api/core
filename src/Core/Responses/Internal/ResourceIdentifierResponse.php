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

namespace LaravelJsonApi\Core\Responses\Internal;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Responses\Concerns;

class ResourceIdentifierResponse implements Responsable
{

    use Concerns\HasRelationshipMeta;
    use Concerns\IsResponsable;

    /**
     * @var JsonApiResource
     */
    private JsonApiResource $resource;

    /**
     * @var string
     */
    private string $fieldName;

    /**
     * @var object|null
     */
    private ?object $related;

    /**
     * ResourceIdentifierResponse constructor.
     *
     * @param JsonApiResource $resource
     * @param string $fieldName
     * @param object|null $related
     */
    public function __construct(JsonApiResource $resource, string $fieldName, ?object $related)
    {
        $this->resource = $resource;
        $this->fieldName = $fieldName;
        $this->related = $related;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function toResponse($request)
    {
        $encoder = $this->server()->encoder();

        $document = $encoder
            ->withRequest($request)
            ->withIncludePaths($this->includePaths($request))
            ->withFieldSets($this->fieldSets($request))
            ->withToOne($this->resource, $this->fieldName, $this->related)
            ->withJsonApi($this->jsonApi())
            ->withMeta($this->allMeta())
            ->withLinks($this->links)
            ->toJson($this->encodeOptions);

        return new Response(
            $document,
            Response::HTTP_OK,
            $this->headers()
        );
    }

    /**
     * @return Hash|null
     */
    private function allMeta(): ?Hash
    {
        return Hash::cast($this->metaForRelationship())
            ->merge($this->meta());
    }

    /**
     * @return array|null
     */
    private function metaForRelationship(): ?array
    {
        if ($this->hasRelationMeta) {
            return $this->resource
                ->relationship($this->fieldName)
                ->meta();
        }

        return null;
    }

}

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

use LaravelJsonApi\Contracts\Resources\Factory as FactoryContract;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LogicException;
use Throwable;
use function array_keys;
use function get_class;
use function is_array;
use function sprintf;

class Factory implements FactoryContract
{

    /**
     * @var SchemaContainer
     *
     */
    protected SchemaContainer $schemas;

    /**
     * @var array
     */
    private array $bindings;

    /**
     * Factory constructor.
     *
     * @param SchemaContainer $schemas
     * @param array|null $bindings
     */
    public function __construct(SchemaContainer $schemas, array $bindings = null)
    {
        $this->schemas = $schemas;
        $this->bindings = is_array($bindings) ? $bindings : $schemas->resources();
    }

    /**
     * @inheritDoc
     */
    public function handles(): iterable
    {
        return array_keys($this->bindings);
    }

    /**
     * @inheritDoc
     */
    public function createResource(object $model): JsonApiResource
    {
        $resource = $this->bindings[get_class($model)] ?? null;

        if (!$resource) {
            throw new LogicException(sprintf(
                'Unexpected model class - %s',
                get_class($model)
            ));
        }

        try {
            return $this->build($resource, $model);
        } catch (Throwable $ex) {
            throw new LogicException(sprintf(
                'Failed to build %s resource object for model %s.',
                $resource,
                get_class($model),
            ), 0, $ex);
        }
    }

    /**
     * Build a new resource object instance.
     *
     * @param string $fqn
     * @param object $model
     * @return JsonApiResource
     */
    protected function build(string $fqn, object $model): JsonApiResource
    {
        return new $fqn(
            $this->schemas->schemaForModel($model),
            $model,
        );
    }

}

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

namespace LaravelJsonApi\Core\Bus\Queries;

use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as DispatcherContract;
use RuntimeException;

class Dispatcher implements DispatcherContract
{
    /**
     * Dispatcher constructor
     *
     * @param Container $container
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @inheritDoc
     */
    public function dispatch(Query $query): Result
    {
        $handler = $this->container->make(
            $binding = $this->handlerFor($query::class),
        );

        assert(
            is_object($handler) && method_exists($handler, 'execute'),
            'Unexpected value from container when resolving query - ' . $query::class,
        );

        $result = $handler->execute($query);

        assert($result instanceof Result, 'Unexpected value returned from query handler: ' . $binding);

        return $result;
    }

    /**
     * @param string $queryClass
     * @return string
     */
    private function handlerFor(string $queryClass): string
    {
        return match ($queryClass) {
            FetchMany\FetchManyQuery::class => FetchMany\FetchManyQueryHandler::class,
            FetchOne\FetchOneQuery::class => FetchOne\FetchOneQueryHandler::class,
            FetchRelated\FetchRelatedQuery::class => FetchRelated\FetchRelatedQueryHandler::class,
            default => throw new RuntimeException('Unexpected query class: ' . $queryClass),
        };
    }
}

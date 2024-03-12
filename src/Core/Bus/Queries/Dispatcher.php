<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries;

use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as DispatcherContract;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
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
            FetchRelationship\FetchRelationshipQuery::class => FetchRelationship\FetchRelationshipQueryHandler::class,
            default => throw new RuntimeException('Unexpected query class: ' . $queryClass),
        };
    }
}

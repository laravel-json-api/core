<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pipeline\Pipeline as PipelineContract;
use Illuminate\Pipeline\Pipeline;

class PipelineFactory
{
    /**
     * PipelineFactory constructor
     *
     * @param Container $container
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Send the value through a new pipeline.
     *
     * @param mixed $passable
     * @return PipelineContract
     */
    public function pipe(mixed $passable): PipelineContract
    {
        $pipeline = new Pipeline($this->container);

        return $pipeline->send($passable);
    }
}

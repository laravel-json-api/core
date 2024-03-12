<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\ShowImplementation;
use LaravelJsonApi\Core\Bus\Queries\Query\Identifiable;
use LaravelJsonApi\Core\Bus\Queries\Query\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Values\ResourceId;

class FetchOneQuery extends Query implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var ShowImplementation|null
     */
    private ?ShowImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param QueryOne $input
     * @return self
     */
    public static function make(?Request $request, QueryOne $input): self
    {
        return new self($request, $input);
    }

    /**
     * FetchOneQuery constructor
     *
     * @param Request|null $request
     * @param QueryOne $input
     */
    public function __construct(
        ?Request $request,
        private readonly QueryOne $input,
    ) {
        parent::__construct($request);
    }

    /**
     * @return ResourceId
     */
    public function id(): ResourceId
    {
        return $this->input->id;
    }

    /**
     * @return QueryOne
     */
    public function input(): QueryOne
    {
        return $this->input;
    }

    /**
     * Set the hooks implementation.
     *
     * @param ShowImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?ShowImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return ShowImplementation|null
     */
    public function hooks(): ?ShowImplementation
    {
        return $this->hooks;
    }
}

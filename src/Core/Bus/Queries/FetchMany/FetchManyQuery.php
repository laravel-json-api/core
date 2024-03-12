<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchMany;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\IndexImplementation;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Query\Input\QueryMany;

class FetchManyQuery extends Query
{
    /**
     * @var IndexImplementation|null
     */
    private ?IndexImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param QueryMany $input
     * @return self
     */
    public static function make(?Request $request, QueryMany $input): self
    {
        return new self($request, $input);
    }

    /**
     * FetchManyQuery constructor
     *
     * @param Request|null $request
     * @param QueryMany $input
     */
    public function __construct(
        ?Request $request,
        private readonly QueryMany $input,
    ) {
        parent::__construct($request);
    }

    /**
     * @return QueryMany
     */
    public function input(): QueryMany
    {
        return $this->input;
    }

    /**
     * Set the hooks implementation.
     *
     * @param IndexImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?IndexImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return IndexImplementation|null
     */
    public function hooks(): ?IndexImplementation
    {
        return $this->hooks;
    }
}

<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Store;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\StoreImplementation;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\HasQuery;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;

class StoreCommand extends Command
{
    use HasQuery;

    /**
     * @var StoreImplementation|null
     */
    private ?StoreImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param Create $operation
     * @return self
     */
    public static function make(?Request $request, Create $operation): self
    {
        return new self($request, $operation);
    }

    /**
     * StoreCommand constructor
     *
     * @param Request|null $request
     * @param Create $operation
     */
    public function __construct(
        ?Request $request,
        private readonly Create $operation
    ) {
        parent::__construct($request);
    }

    /**
     * @inheritDoc
     */
    public function operation(): Create
    {
        return $this->operation;
    }

    /**
     * Set the hooks implementation.
     *
     * @param StoreImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?StoreImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return StoreImplementation|null
     */
    public function hooks(): ?StoreImplementation
    {
        return $this->hooks;
    }
}

<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Destroy;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\DestroyImplementation;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\Identifiable;
use LaravelJsonApi\Core\Bus\Commands\Command\IsIdentifiable;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Values\ResourceId;

class DestroyCommand extends Command implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var DestroyImplementation|null
     */
    private ?DestroyImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param Delete $operation
     * @return self
     */
    public static function make(?Request $request, Delete $operation): self
    {
        return new self($request, $operation);
    }

    /**
     * DestroyCommand constructor
     *
     * @param Request|null $request
     * @param Delete $operation
     */
    public function __construct(?Request $request, private readonly Delete $operation)
    {
        parent::__construct($request);
    }

    /**
     * @inheritDoc
     */
    public function id(): ResourceId
    {
        $id = $this->operation->ref()->id;

        assert($id !== null, 'Expecting a delete operation with a ref that has an id.');

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function operation(): Delete
    {
        return $this->operation;
    }

    /**
     * Set the hooks implementation.
     *
     * @param DestroyImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?DestroyImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return DestroyImplementation|null
     */
    public function hooks(): ?DestroyImplementation
    {
        return $this->hooks;
    }
}

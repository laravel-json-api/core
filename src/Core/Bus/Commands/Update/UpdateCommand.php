<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Update;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateImplementation;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\HasQuery;
use LaravelJsonApi\Core\Bus\Commands\Command\Identifiable;
use LaravelJsonApi\Core\Bus\Commands\Command\IsIdentifiable;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Values\ResourceId;
use RuntimeException;

class UpdateCommand extends Command implements IsIdentifiable
{
    use Identifiable;
    use HasQuery;

    /**
     * @var UpdateImplementation|null
     */
    private ?UpdateImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param Update $operation
     * @return self
     */
    public static function make(?Request $request, Update $operation): self
    {
        return new self($request, $operation);
    }

    /**
     * UpdateCommand constructor
     *
     * @param Request|null $request
     * @param Update $operation
     */
    public function __construct(
        ?Request $request,
        private readonly Update $operation,
    ) {
        parent::__construct($request);
    }

    /**
     * @inheritDoc
     */
    public function id(): ResourceId
    {
        if ($id = $this->operation->data->id) {
            return $id;
        }

        throw new RuntimeException('Expecting resource object on update operation to have a resource id.');
    }

    /**
     * @inheritDoc
     */
    public function operation(): Update
    {
        return $this->operation;
    }

    /**
     * Set the hooks implementation.
     *
     * @param UpdateImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?UpdateImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return UpdateImplementation|null
     */
    public function hooks(): ?UpdateImplementation
    {
        return $this->hooks;
    }
}

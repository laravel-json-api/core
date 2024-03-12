<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelationshipImplementation;
use LaravelJsonApi\Core\Bus\Queries\Query\Identifiable;
use LaravelJsonApi\Core\Bus\Queries\Query\IsRelatable;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;
use LaravelJsonApi\Core\Values\ResourceId;

class FetchRelationshipQuery extends Query implements IsRelatable
{
    use Identifiable;

    /**
     * @var ShowRelationshipImplementation|null
     */
    private ?ShowRelationshipImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param QueryRelationship $input
     * @return self
     */
    public static function make(?Request $request, QueryRelationship $input): self
    {
        return new self($request, $input);
    }

    /**
     * FetchRelationshipQuery constructor
     *
     * @param Request|null $request
     * @param QueryRelationship $input
     */
    public function __construct(
        ?Request $request,
        private readonly QueryRelationship $input,
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
     * @return string
     */
    public function fieldName(): string
    {
        return $this->input->fieldName;
    }

    /**
     * @return QueryRelationship
     */
    public function input(): QueryRelationship
    {
        return $this->input;
    }

    /**
     * Set the hooks implementation.
     *
     * @param ShowRelationshipImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?ShowRelationshipImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return ShowRelationshipImplementation|null
     */
    public function hooks(): ?ShowRelationshipImplementation
    {
        return $this->hooks;
    }
}

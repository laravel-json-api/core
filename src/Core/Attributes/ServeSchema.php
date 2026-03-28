<?php

declare(strict_types=1);

namespace LaravelJsonApi\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ServeSchema
{
    /**
     * @param array|class-string $schema
     */
    public function __construct(
        public array|string $schema,
    ) {}
}

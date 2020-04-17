<?php

declare(strict_types=1);

namespace LaravelJsonApi\Core\Contracts\Document;

interface ResourceFactory
{

    /**
     * @param mixed $record
     * @return ResourceObject
     */
    public function create($record): ResourceObject;

    /**
     * @param iterable $records
     * @return iterable
     */
    public function cursor(iterable $records): iterable;
}

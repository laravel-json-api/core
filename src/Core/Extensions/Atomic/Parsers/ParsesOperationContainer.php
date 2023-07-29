<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Parsers;

use Generator;
use LaravelJsonApi\Core\Document\Input\Parsers\ListOfResourceIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierParser;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use RuntimeException;

class ParsesOperationContainer
{
    /**
     * @var array<string,ParsesOperationFromArray>
     */
    private array $cache = [];

    /**
     * @var HrefOrRefParser|null
     */
    private ?HrefOrRefParser $targetParser = null;

    /**
     * @var RefParser|null
     */
    private ?RefParser $refParser = null;

    /**
     * @var ResourceObjectParser|null
     */
    private ?ResourceObjectParser $resourceObjectParser = null;

    /**
     * @var ResourceIdentifierParser|null
     */
    private ?ResourceIdentifierParser $identifierParser = null;

    /**
     * @param OpCodeEnum $op
     * @return Generator<int,ParsesOperationFromArray>
     */
    public function cursor(OpCodeEnum $op): Generator
    {
        $parsers = match ($op) {
            OpCodeEnum::Add => [
                CreateParser::class,
                UpdateToManyParser::class,
            ],
            OpCodeEnum::Update => [
                UpdateParser::class,
                UpdateToOneParser::class,
                UpdateToManyParser::class,
            ],
            OpCodeEnum::Remove => [
                DeleteParser::class,
                UpdateToManyParser::class,
            ],
        };

        foreach ($parsers as $parser) {
            yield $this->cache[$parser] ?? $this->make($parser);
        }
    }

    /**
     * @param string $parser
     * @return ParsesOperationFromArray
     */
    private function make(string $parser): ParsesOperationFromArray
    {
        return $this->cache[$parser] = match ($parser) {
            CreateParser::class => new CreateParser($this->getResourceObjectParser()),
            UpdateParser::class => new UpdateParser(
                $this->getTargetParser(),
                $this->getResourceObjectParser(),
            ),
            DeleteParser::class => new DeleteParser($this->getTargetParser()),
            UpdateToOneParser::class => new UpdateToOneParser(
                $this->getTargetParser(),
                $this->getResourceIdentifierParser(),
            ),
            UpdateToManyParser::class => new UpdateToManyParser(
                $this->getTargetParser(),
                new ListOfResourceIdentifiersParser($this->getResourceIdentifierParser()),
            ),
            default => throw new RuntimeException('Unexpected operation parser class: ' . $parser),
        };
    }

    /**
     * @return HrefOrRefParser
     */
    private function getTargetParser(): HrefOrRefParser
    {
        if ($this->targetParser) {
            return $this->targetParser;
        }

        return $this->targetParser = new HrefOrRefParser($this->getRefParser());
    }

    /**
     * @return RefParser
     */
    private function getRefParser(): RefParser
    {
        if ($this->refParser) {
            return $this->refParser;
        }

        return $this->refParser = new RefParser();
    }

    /**
     * @return ResourceObjectParser
     */
    private function getResourceObjectParser(): ResourceObjectParser
    {
        if ($this->resourceObjectParser) {
            return $this->resourceObjectParser;
        }

        return $this->resourceObjectParser = new ResourceObjectParser();
    }

    /**
     * @return ResourceIdentifierParser
     */
    private function getResourceIdentifierParser(): ResourceIdentifierParser
    {
        if ($this->identifierParser) {
            return $this->identifierParser;
        }

        return $this->identifierParser = new ResourceIdentifierParser();
    }
}

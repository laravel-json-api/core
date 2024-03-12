<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Parsers;

use Generator;
use LaravelJsonApi\Contracts\Server\Server;
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
     * @var HrefParser|null
     */
    private ?HrefParser $hrefParser = null;

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
     * ParsesOperationContainer constructor
     *
     * @param Server $server
     */
    public function __construct(private readonly Server $server)
    {
    }

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
            CreateParser::class => new CreateParser(
                $this->getHrefParser(),
                $this->getResourceObjectParser(),
            ),
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
     * @return HrefParser
     */
    private function getHrefParser(): HrefParser
    {
        if ($this->hrefParser) {
            return $this->hrefParser;
        }

        return $this->hrefParser = new HrefParser($this->server);
    }

    /**
     * @return HrefOrRefParser
     */
    private function getTargetParser(): HrefOrRefParser
    {
        if ($this->targetParser) {
            return $this->targetParser;
        }

        return $this->targetParser = new HrefOrRefParser(
            $this->getHrefParser(),
            $this->getRefParser(),
        );
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

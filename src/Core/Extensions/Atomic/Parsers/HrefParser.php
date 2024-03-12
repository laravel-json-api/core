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

use Illuminate\Support\Str;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Values\ResourceId;
use RuntimeException;

class HrefParser
{
    /** @var string */
    private const REGEX = '/^\/([a-zA-Z0-9_\-]+)(\/([a-zA-Z0-9_\-]+)(\/relationships\/([a-zA-Z0-9_\-]+))?)?$/m';

    /**
     * HrefParser constructor
     *
     * @param Server $server
     */
    public function __construct(private readonly Server $server)
    {
    }

    /**
     * Safely parse the string href.
     *
     * @param Href|string $href
     * @return ParsedHref|null
     */
    public function safe(Href|string $href): ?ParsedHref
    {
        $values = $this->extract($href);
        $schemas = $this->server->schemas();
        $type = isset($values['type']) ? $schemas->schemaTypeForUri($values['type']) : null;

        if ($type === null) {
            return null;
        }

        $schema = $schemas->schemaFor($type);
        $href = ($href instanceof Href) ? $href : new Href($href);
        $id = isset($values['id']) ? new ResourceId($values['id']) : null;

        if ($id && !$schema->id()->match($id->value)) {
            return null;
        }

        if (isset($values['relationship'])) {
            $relation = $schema->relationshipForUri($values['relationship']);
            return $relation ? new ParsedHref(
                href: $href,
                type: $type,
                id: $id,
                relationship: $relation->name(),
            ) : null;
        }

        return new ParsedHref(href: $href, type: $type, id: $id);
    }

    /**
     * Parse the string href.
     *
     * @param Href|string $href
     * @return ParsedHref
     */
    public function parse(Href|string $href): ParsedHref
    {
        return $this->safe($href) ?? throw new RuntimeException('Invalid href: ' . $href);
    }

    /**
     * @param Href|string|null $href
     * @return ParsedHref|null
     */
    public function nullable(Href|string|null $href): ?ParsedHref
    {
        if ($href !== null) {
            return $this->parse($href);
        }

        return null;
    }

    /**
     * If parsed, will the href have a relationship?
     *
     * @param string $href
     * @return bool
     */
    public function hasRelationship(string $href): bool
    {
        return 1 === preg_match('/relationships\/([a-zA-Z0-9_\-]+)$/', $href);
    }

    /**
     * @param Href|string $href
     * @return array
     */
    private function extract(Href|string $href): array
    {
        $serverUrl = parse_url($this->server->url());
        $hrefUrl = parse_url((string) $href);
        $after = Str::after($hrefUrl['path'], $serverUrl['path']);

        if (1 === preg_match(self::REGEX, $after, $matches)) {
            return [
                'type' => $matches[1],
                'id' => $matches[3] ?? null,
                'relationship' => $matches[5] ?? null,
            ];
        }

        return [];
    }
}

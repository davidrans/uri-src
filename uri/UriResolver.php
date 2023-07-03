<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Contracts\UriInterface;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;

/**
 * @deprecated since version 7.0.0
 * @codeCoverageIgnore
 * @see BaseUri
 */
final class UriResolver
{
    /**
     * Resolves a URI against a base URI using RFC3986 rules.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter or silence them apart from validating its own parameters.
     */
    public static function resolve(Stringable|string $uri, Stringable|string $baseUri): Psr7UriInterface|UriInterface
    {
        return BaseUri::new($baseUri)->resolve($uri)->uri();
    }

    /**
     * Relativizes a URI according to a base URI.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter or silence them apart from validating its own parameters.
     */
    public static function relativize(Stringable|string $uri, Stringable|string $baseUri): Psr7UriInterface|UriInterface
    {
        return BaseUri::new($baseUri)->relativize($uri)->uri();
    }
}

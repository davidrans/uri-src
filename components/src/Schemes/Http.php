<?php
/**
 * League.Url (http://url.thephpleague.com)
 *
 * @package   League.uri
 * @author    Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/uri/blob/master/LICENSE (MIT License)
 * @version   4.0.0
 * @link      https://github.com/thephpleague/uri/
 */
namespace League\Uri\Schemes;

use InvalidArgumentException;
use League\Uri\Components\Host;
use League\Uri\Interfaces\Uri;
use League\Uri\Schemes\Generic\AbstractHierarchicalUri;
use League\Uri\UriParser;
use Psr\Http\Message\UriInterface;

/**
 * Value object representing HTTP and HTTPS Uri.
 *
 * @package League.uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   4.0.0
 */
class Http extends AbstractHierarchicalUri implements Uri, UriInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $supportedSchemes = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * {@inheritdoc}
     */
    protected function isValid()
    {
        return $this->isValidGenericUri()
            && $this->isValidHierarchicalUri();
    }

    /**
     * Create a new instance from the environment
     *
     * @param array $server the server and execution environment information array typically ($_SERVER)
     *
     * @throws InvalidArgumentException If the URI can not be parsed
     *
     * @return static
     */
    public static function createFromServer(array $server)
    {
        return static::createFromString(
            static::fetchServerScheme($server).'//'
            .static::fetchServerUserInfo($server)
            .static::fetchServerHost($server)
            .static::fetchServerPort($server)
            .static::fetchServerRequestUri($server)
        );
    }

    /**
     * Returns the environment scheme
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerScheme(array $server)
    {
        $server = array_merge(['HTTPS' => ''], $server);
        $res = filter_var($server['HTTPS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return ($res !== false) ? 'https:' : 'http:';
    }

    /**
     * Returns the environment host
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @throws InvalidArgumentException If the host can not be detected
     *
     * @return string
     */
    protected static function fetchServerHost(array $server)
    {
        if (isset($server['HTTP_HOST'])) {
            return static::fetchServerHostname($server['HTTP_HOST']);
        }

        if (isset($server['SERVER_ADDR'])) {
            return (string) new Host($server['SERVER_ADDR']);
        }

        throw new InvalidArgumentException('Host could not be detected');
    }

    /**
     * Returns the environment hostname
     *
     * @param string $host the environment server hostname
     *                     the port info can sometimes be
     *                     associated with the hostname
     *
     * @return string
     */
    protected static function fetchServerHostname($host)
    {
        preg_match(",^(([^(\[\])]*):)?(?<host>.*)?$,", strrev($host), $matches);

        return strrev($matches['host']);
    }

    /**
     * Returns the environment user info
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerUserInfo(array $server)
    {
        $server = array_merge(['PHP_AUTH_USER' => null, 'PHP_AUTH_PW' => null], $server);

        return (new UriParser())->buildUserInfo($server['PHP_AUTH_USER'], $server['PHP_AUTH_PW']);
    }

    /**
     * Returns the environment port
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerPort(array $server)
    {
        $server = array_merge(['HTTP_HOST' => '', 'SERVER_PORT' => ''], $server);
        if (preg_match(',^(?<port>([^(\[\])]*):),', strrev($server['HTTP_HOST']), $matches)) {
            return strrev($matches['port']);
        }

        return ':'.$server['SERVER_PORT'];
    }

    /**
     * Returns the environment path
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerRequestUri(array $server)
    {
        if (isset($server['REQUEST_URI'])) {
            return $server['REQUEST_URI'];
        }

        $server = array_merge(['PHP_SELF' => '', 'QUERY_STRING' => ''], $server);

        return $server['PHP_SELF'].'?'.$server['QUERY_STRING'];
    }
}

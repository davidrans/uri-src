<?php
/**
* This file is part of the League.url library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/url/
* @version 4.0.0
* @package League.url
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace League\Url;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use League\Url\Interfaces;
use Traversable;

/**
 * An abstract class to ease component creation
 *
 * @package  League.url
 * @since  1.0.0
 */
class Query extends AbstractComponent implements Interfaces\Query
{
    /**
     * The Component Data
     *
     * @var array
     */
    protected $data = [];

    /**
     * a new instance
     *
     * @param string $data
     */
    public function __construct($data = null)
    {
        if (! is_null($data)) {
            $this->data = $this->validate($data);
        }
    }

    /**
     * return a new Query instance from an Array or a traversable object
     *
     * @param  \Traversable|array $data
     *
     * @throws \InvalidArgumentException If $data is invalid
     *
     * @return static
     */
    public static function createFromArray($data)
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data, true);
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('Data passed to the method must be an array or a Traversable object');
        }

        return new static(http_build_query($data, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * sanitize the submitted data
     *
     * @param string $str
     *
     * @return array
     */
    protected function validate($str)
    {
        if (is_bool($str)) {
            throw new InvalidArgumentException('Data passed must be a valid string; received a boolean');
        }

        $str = $this->validateString($str);
        if (empty($str)) {
            return [];
        }

        $str = preg_replace_callback('/(?:^|(?<=&))[^=|&[]+/', function ($match) {
            return bin2hex(urldecode($match[0]));
        }, $str);
        parse_str($str, $arr);

        $arr = array_combine(array_map('hex2bin', array_keys($arr)), $arr);

        return array_filter($arr, function ($value) {
            return ! is_null($value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (empty($this->data)) {
            return null;
        }

        return preg_replace(
            [',=&,', ',=$,'],
            ['&', ''],
            http_build_query($this->data, '', '&', PHP_QUERY_RFC3986)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getUriComponent()
    {
        $res = $this->__toString();
        if (empty($res)) {
            return $res;
        }

        return '?'.$res;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOffset($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsets($parameter = null)
    {
        if (is_null($parameter)) {
            return array_keys($this->data);
        }

        return array_keys($this->data, $parameter, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter($offset, $default = null)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function without(array $offsets)
    {
        $data = $this->data;
        foreach (array_unique($offsets) as $offset) {
            unset($data[$offset]);
        }

        return static::createFromArray($data);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(Interfaces\Query $query)
    {
        return static::createFromArray(array_merge($this->data, $query->data));
    }
}

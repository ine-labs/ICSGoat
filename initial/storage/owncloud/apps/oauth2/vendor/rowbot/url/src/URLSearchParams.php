<?php
namespace Rowbot\URL;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use Rowbot\URL\Exception\TypeError;
use Traversable;

use function array_column;
use function count;
use function func_num_args;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function mb_substr;

/**
 * An object containing a list of all URL query parameters.  This allows you to
 * manipulate a URL's query string in a granular manner.
 *
 * @see https://url.spec.whatwg.org/#urlsearchparams
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
 */
class URLSearchParams implements Iterator
{
    use URLFormEncoded;

    /**
     * @var \Rowbot\URL\QueryList
     */
    private $list;

    /**
     * @var \Rowbot\URL\URLRecord|null
     */
    private $url;

    /**
     * Constructor.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-urlsearchparams
     *
     * @param self|array<array<string>>|object|string $init (optional)
     *
     * @return void
     */
    public function __construct($init = '')
    {
        $this->list = new QueryList();
        $this->url = null;

        // If $init is given, is a string, and starts with "?", remove the
        // first code point from $init.
        if (func_num_args() > 0) {
            try {
                // Try to get a string value, otherwise just pass the value on.
                $init = URLUtils::strval($init);
            } catch (InvalidArgumentException $e) {
                // Do nothing.
            }

            if (is_string($init) && mb_substr($init, 0, 1, 'UTF-8') === '?') {
                $init = mb_substr($init, 1, null, 'UTF-8');
            }

            $this->init($init);
        }
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->list = clone $this->list;

        // Null out the url incase someone tries cloning the object returned by
        // the URL::searchParams attribute.
        $this->url = null;
    }

    /**
     * Returns all name-value pairs stringified in the correct order.
     *
     * @return string The query string.
     */
    public function __toString()
    {
        return $this->urlencodeList($this->list->all());
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->urlencodeList($this->list->all());
    }

    /**
     * @see https://url.spec.whatwg.org/#concept-urlsearchparams-new
     *
     * @internal
     *
     * @param string                $init The query string or another URLSearchParams object.
     * @param \Rowbot\URL\URLRecord $url  The associated URLRecord object.
     *
     * @return self
     */
    public static function create($init, URLRecord $url)
    {
        $query = new self();
        $query->url = $url;
        $query->init($init);

        return $query;
    }

    /**
     * @internal
     *
     * @param self|array<array<string>>|object|string $init
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     * @throws \Rowbot\URL\Exception\TypeError
     */
    private function init($init)
    {
        if (is_array($init) || $init instanceof Traversable) {
            foreach ($init as $pair) {
                // Try to catch cases where $pair isn't countable or $pair is
                // countable, but isn't a valid sequence, such as:
                //
                // class CountableClass implements \Countable
                // {
                //     public function count()
                //     {
                //         return 2;
                //     }
                // }
                //
                // $s = new \Rowbot\URL\URLSearchParams([new CountableClass()]);
                //
                // or:
                //
                // $a = new \ArrayObject(['x', 'y']);
                // $s = new \Rowbot\URL\URLSearchParams($a);
                //
                // while still allowing things like:
                //
                // $a = new \ArrayObject(new \ArrayObject(['x', 'y']));
                // $s = new \Rowbot\URL\URLSearchParams($a);'
                if (!is_array($pair)
                    && (!$pair instanceof ArrayAccess
                        || !$pair instanceof Countable)
                ) {
                    throw new InvalidArgumentException(sprintf(
                        'Expected a valid sequence such as an Array or Object '
                        . 'that implements both the ArrayAccess and Countable '
                        . 'interfaces. %s found instead.',
                        gettype($pair)
                    ));
                }

                if (count($pair) != 2) {
                    throw new TypeError(sprintf(
                        'Expected sequence with excatly 2 items. Sequence '
                        . 'contained %d items.',
                        count($pair)
                    ));
                }
            }

            foreach ($init as $pair) {
                $this->list->append(
                    URLUtils::strval($pair[0]),
                    URLUtils::strval($pair[1])
                );
            }

            return;
        }

        if (is_object($init)) {
            foreach ($init as $name => $value) {
                $this->append(
                    URLUtils::strval($name),
                    URLUtils::strval($value)
                );
            }

            return;
        }

        if (is_string($init)) {
            $pairs = $this->urldecodeString($init);

            foreach ($pairs as $pair) {
                $this->list->append($pair['name'], $pair['value']);
            }
        }
    }

    /**
     * Appends a new name-value pair to the end of the query string.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-append
     *
     * @param string $name  The name of the key in the pair.
     * @param string $value The value assigned to the key.
     *
     * @return void
     */
    public function append($name, $value)
    {
        $this->list->append(URLUtils::strval($name), URLUtils::strval($value));
        $this->update();
    }

    /**
     * Deletes all occurances of pairs with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-delete
     *
     * @param string $name The name of the key to delete.
     *
     * @return void
     */
    public function delete($name)
    {
        $this->list->remove(URLUtils::strval($name));
        $this->update();
    }

    /**
     * Get the value of the first name-value pair with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-get
     *
     * @param string $name The name of the key whose value you want to retrive.
     *
     * @return string|null The value of the specified key.
     */
    public function get($name)
    {
        return $this->list->first(URLUtils::strval($name));
    }

    /**
     * Gets all name-value pairs that has the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-getall
     *
     * @param string $name The name of the key whose values you want to retrieve.
     *
     * @return array<array<string>> An array containing all the values of the specified key.
     */
    public function getAll($name)
    {
        $name = URLUtils::strval($name);

        return array_column($this->list->filter(function ($pair) use ($name) {
            return $pair['name'] === $name;
        }), 'value');
    }

    /**
     * Indicates whether or not a query string contains any keys with the
     * specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-has
     *
     * @param string $name The key name you want to test if it exists.
     *
     * @return bool Returns true if the key exits, otherwise false.
     */
    public function has($name)
    {
        return $this->list->contains(URLUtils::strval($name));
    }

    /**
     * Sets the value of the specified key name.  If multiple pairs exist with
     * the same key name it will set the value for the first occurance of the
     * key in the query string and all other occurances will be removed from the
     * query string.  If the key does not already exist in the query string, it
     * will be added to the end of the query string.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-set
     *
     * @param string $name  The name of the key you want to modify the value of.
     * @param string $value The value you want to associate with the key name.
     *
     * @return void
     */
    public function set($name, $value)
    {
        $name = URLUtils::strval($name);
        $value = URLUtils::strval($value);

        if ($this->list->contains($name)) {
            $this->list->set($name, $value);
        } else {
            $this->list->append($name, $value);
        }

        $this->update();
    }

    /**
     * Sorts the list of search params by their names by comparing their code
     * unit values, preserving the relative order between pairs with the same
     * name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-sort
     *
     * @return void
     */
    public function sort()
    {
        $this->list->sort();
        $this->update();
    }

    /**
     * Clears the list of search params.
     *
     * @internal
     *
     * @return void
     */
    public function clear()
    {
        $this->list->clear();
    }

    /**
     * Mutates the the list of query parameters without going through the
     * public API.
     *
     * @internal
     *
     * @param array<array<string, string>> $list A list of name-value pairs to
     *                                           be added to the list.
     *
     * @return void
     */
    public function modify(array $list)
    {
        $this->list->update($list);
    }

    /**
     * Set's the associated URL object's query to the serialization of
     * URLSearchParams.
     *
     * @see https://url.spec.whatwg.org/#concept-urlsearchparams-update
     *
     * @internal
     *
     * @return void
     */
    protected function update()
    {
        if ($this->url === null) {
            return;
        }

        $query = $this->urlencodeList($this->list->all());

        if ($query === '') {
            $query = null;
        }

        $this->url->query = $query;
    }

    /**
     * Sets the url.
     *
     * @internal
     *
     * @param \Rowbot\URL\URLRecord $url
     *
     * @return void
     */
    public function setUrl(URLRecord $url)
    {
        $this->url = $url;
    }

    /**
     * @return string[]
     */
    public function current()
    {
        $current = $this->list->current();

        return [$current['name'], $current['value']];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->list->key();
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->list->next();
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->list->rewind();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->list->valid();
    }
}

<?php
namespace Rowbot\URL;

use Countable;
use IntlBreakIterator;
use Iterator;

use function count;
use function array_filter;
use function array_splice;
use function usort;

class QueryList implements Countable, Iterator
{
    /**
     * @var array<string, bool>
     */
    private $cache;

    /**
     * @var int
     */
    private $cursor;

    /**
     * @var array<int, array<string, string>>
     */
    private $list;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->list = [];
        $this->cache = [];
        $this->cursor = 0;
    }

    /**
     * Appends a new name-value pair to the list.
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function append($name, $value)
    {
        $this->list[] = ['name' => $name, 'value' => $value];
        $this->cache[$name] = true;
    }

    /**
     * Removes all name-value pairs with name $name from the list.
     *
     * @param string $name
     *
     * @return void
     */
    public function remove($name)
    {
        for ($i = count($this->list) - 1; $i >= 0; $i--) {
            if ($this->list[$i]['name'] === $name) {
                array_splice($this->list, $i, 1);
            }
        }

        unset($this->cache[$name]);
    }

    /**
     * Determines if a name-value pair with name $name exists in the collection.
     *
     * @param string $name
     *
     * @return bool
     */
    public function contains($name)
    {
        return isset($this->cache[$name]);
    }

    /**
     * Returns the first name-value pair in the list whose name is $name.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function first($name)
    {
        foreach ($this->list as $pair) {
            if ($pair['name'] === $name) {
                return $pair['value'];
            }
        }

        return null;
    }

    /**
     * Returns a filtered array based on the given callback.
     *
     * @param callable $callback
     *
     * @return array<int, array<string, string>>
     */
    public function filter(callable $callback)
    {
        return array_filter($this->list, $callback);
    }

    /**
     * Sets the value of the first name-value pair with $name to $value and
     * removes all other occurances that have name $name.
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function set($name, $value)
    {
        $prevIndex = null;

        for ($i = count($this->list) - 1; $i >= 0; $i--) {
            if ($this->list[$i]['name'] === $name) {
                if ($prevIndex !== null) {
                    array_splice($this->list, $prevIndex, 1);
                }

                $prevIndex = $i;
            }
        }

        $this->list[$prevIndex]['value'] = $value;
    }

    /**
     * Returns the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->list);
    }

    /**
     * Sorts the collection by code units and preserves the relative positioning
     * of name-value pairs.
     *
     * @return void
     */
    public function sort()
    {
        $temp = [];
        $i = 0;

        // Add information about the relative position of each name-value pair.
        foreach ($this->list as $pair) {
            $temp[] = [$i++, $pair];
        }

        // Gasp! What is this black magic?
        $breakIterator1 = IntlBreakIterator::createCodePointInstance();
        $breakIterator2 = IntlBreakIterator::createCodePointInstance();
        $iterator1 = $breakIterator1->getPartsIterator();
        $iterator2 = $breakIterator2->getPartsIterator();

        // Sorting priority overview:
        //
        // Each string is compared character by character against each other.
        //
        // 1) If the two strings have different lengths, and the strings are
        //    equal up to the end of the shortest string, then the shorter of
        //    the two strings will be moved up in the array (ex. "aa" will come
        //    before "aaa").
        // 2) If the number of code units differ between the two characters,
        //    then the character with more code units will be moved up in the
        //    array (ex. "🌈" will come before "ﬃ").
        // 3) If the code points of the two characters are different, then the
        //    first string with a character with a lower code point will be
        //    moved up in the array (ex. "bba" will come before "bbb").
        usort($temp, function (
            $a,
            $b
        ) use (
            $breakIterator1,
            $breakIterator2,
            $iterator1,
            $iterator2
        ) {
            $breakIterator1->setText($a[1]['name']);
            $breakIterator2->setText($b[1]['name']);
            $iterator1->rewind();
            $iterator2->rewind();

            while (true) {
                $aIsValid = $iterator1->valid();
                $bIsValid = $iterator2->valid();

                if (!$aIsValid && !$bIsValid) {
                    // If we have reached this point then there are 2
                    // possibilities:
                    //
                    // 1) Both $a and $b are an empty string.
                    // 2) Both $a and $b have the same length and are considered
                    //    equal to each other.
                    //
                    // In either case, break out of the loop as we now need to
                    // compare the relative position of $a and $b to each other
                    // to settle the tie.
                    break;
                }

                if (!$aIsValid) {
                    // If we have reached this point then there are 2
                    // possibilities:
                    //
                    // 1) $a is an empty string and $b is not.
                    // 2) $a is a non-empty string, but its length is shorter
                    //    than $b.
                    //
                    // $a and $b are considered equal thus far, but $a is
                    // shorter so it gets to come before $b.
                    return -1;
                }

                if (!$bIsValid) {
                    // If we have reached this point then there are 2
                    // possibilities:
                    //
                    // 1) $b is an empty string and $a is not.
                    // 2) $b is a non-empty string, but its length is shorter
                    //    than $a.
                    //
                    // $a and $b are considered equal thus far, but $b is
                    // shorter so it gets to come before $a.
                    return 1;
                }

                $aCodePoint = $breakIterator1->getLastCodePoint();
                $bCodePoint = $breakIterator2->getLastCodePoint();

                // JavaScript likes to be fancy by using UTF-16 encoded strings.
                // In UTF-16, all code points in the Basic Multilingual Plane
                // (BMP) are represented by a single code unit. Code points
                // outside of the BMP (> 0xFFFF) are represented by 2 code
                // units.
                $aCodeUnits = $aCodePoint > 0xFFFF ? 2 : 1;
                $bCodeUnits = $bCodePoint > 0xFFFF ? 2 : 1;

                if ($aCodeUnits > $bCodeUnits) {
                    return -1;
                } elseif ($aCodeUnits < $bCodeUnits) {
                    return 1;
                } elseif ($aCodePoint > $bCodePoint) {
                    return 1;
                } elseif ($aCodePoint < $bCodePoint) {
                    return -1;
                }

                $iterator1->next();
                $iterator2->next();
            }

            // Finally, if all else is equal, sort by relative position.
            return $a[0] - $b[0];
        });

        // Remove the relative positioning information so that $temp only
        // contains the sorted name-value pairs.
        foreach ($temp as &$item) {
            $item = $item[1];
        }

        $this->list = $temp;
    }

    /**
     * Clears the collection and cache.
     *
     * @return void
     */
    public function clear()
    {
        $this->list = [];
        $this->cache = [];
    }

    /**
     * Clears the collection and cache and then fills the collection with the
     * new name-value pairs in $list.
     *
     * @param array<int, array<string, string>> $list
     *
     * @return void
     */
    public function update(array $list)
    {
        $this->list = [];
        $this->cache = [];

        foreach ($list as $pair) {
            $this->list[] = $pair;
            $this->cache[$pair['name']] = true;
        }
    }

    /**
     * Returns the entire collection as an array.
     *
     * @return array<int, array<string, string>>
     */
    public function all()
    {
        return $this->list;
    }

    /**
     * Returns the current name-value pair.
     *
     * @return array<string, string>
     */
    public function current()
    {
        return $this->list[$this->cursor];
    }

    /**
     * Returns the iterator key.
     *
     * @return int
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * Advances the iterator to the next position.
     *
     * @return void
     */
    public function next()
    {
        ++$this->cursor;
    }

    /**
     * Rewinds the iterator to the beginning.
     *
     * @return void
     */
    public function rewind()
    {
        $this->cursor = 0;
    }

    /**
     * Returns whether the iterator is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->list[$this->cursor]);
    }
}

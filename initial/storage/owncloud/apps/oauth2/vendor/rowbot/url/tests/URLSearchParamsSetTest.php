<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-set.html
 */
class URLSearchParamsSetTest extends PHPUnit_Framework_TestCase
{
    public function testSetBasics()
    {
        $params = new URLSearchParams('a=b&c=d');
        $params->set('a', 'B');
        $this->assertEquals('a=B&c=d', $params . '');
        $params = new URLSearchParams('a=b&c=d&a=e');
        $params->set('a', 'B');
        $this->assertEquals('a=B&c=d', $params . '');
        $params->set('e', 'f');
        $this->assertEquals('a=B&c=d&e=f', $params . '');
    }

    public function testURLSearchParamsSet()
    {
        $params = new URLSearchParams('a=1&a=2&a=3');

        $this->assertTrue(
            $params->has('a'),
            'Search params object has name "a"'
        );
        $this->assertEquals(
            '1',
            $params->get('a'),
            'Search params object has name "a" with a value of "1"'
        );

        $params->set('first', 4);

        $this->assertTrue(
            $params->has('a'),
            'Search params object has name "a"'
        );
        $this->assertEquals(
            '1',
            $params->get('a'),
            'Search params object has name "a" with value "1"'
        );

        $params->set('a', 4);

        $this->assertTrue(
            $params->has('a'),
            'Search params object has name "a"'
        );
        $this->assertSame(
            '4',
            $params->get('a'),
            'Search params object has name "a" with value "4"'
        );
    }
}

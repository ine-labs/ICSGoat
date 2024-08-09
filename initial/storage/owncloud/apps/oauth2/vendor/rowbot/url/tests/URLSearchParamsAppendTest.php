<?php
namespace Rowbot\URL\Tests;

use PHPUnit_Framework_TestCase;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-append.html
 */
class URLSearchParamsAppendTest extends PHPUnit_Framework_TestCase
{
    public function testAppendSameName()
    {
        $params = new URLSearchParams();
        $params->append('a', 'b');
        $this->assertEquals('a=b', $params . '');
        $params->append('a', 'b');
        $this->assertEquals('a=b&a=b', $params . '');
        $params->append('a', 'c');
        $this->assertEquals('a=b&a=b&a=c', $params . '');
    }

    public function testAppendEmptyString()
    {
        $params = new URLSearchParams();
        $params->append('', '');
        $this->assertEquals('=', $params . '');
        $params->append('', '');
        $this->assertEquals('=&=', $params . '');
        $params->append('a', 'c');
    }

    public function testAppendMultiple()
    {
        $params = new URLSearchParams();
        $params->append('first', 1);
        $params->append('second', 2);
        $params->append('third', '');
        $params->append('first', 10);
        $this->assertTrue($params->has('first'));
        $this->assertEquals('1', $params->get('first'));
        $this->assertEquals('2', $params->get('second'));
        $this->assertEquals('', $params->get('third'));
        $params->append('first', 10);
        $this->assertEquals('1', $params->get('first'));
    }
}

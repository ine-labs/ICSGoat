<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;
use Rowbot\URL\URL;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-delete.html
 */
class URLSearchParamsDeleteTest extends PHPUnit_Framework_TestCase
{
    public function testDeleteBasics()
    {
        $params = new URLSearchParams('a=b&c=d');
        $params->delete('a');
        $this->assertEquals('c=d', $params . '');
        $params = new URLSearchParams('a=a&b=b&a=a&c=c');
        $params->delete('a');
        $this->assertEquals('b=b&c=c', $params . '');
        $params = new URLSearchParams('a=a&=&b=b&c=c');
        $params->delete('');
        $this->assertEquals('a=a&b=b&c=c', $params . '');
    }

    public function testDeleteAppendMultiple()
    {
        $params = new URLSearchParams();
        $params->append('first', 1);
        $this->assertTrue($params->has('first'));
        $this->assertEquals('1', $params->get('first'));
        $params->delete('first');
        $this->assertFalse($params->has('first'));
        $params->append('first', 1);
        $params->append('first', 10);
        $params->delete('first');
        $this->assertFalse($params->has('first'));
    }

    public function testDeleteAllRemovesQuestionMark()
    {
        $url = new URL('http://example.com/?param1&param2');
        $url->searchParams->delete('param1');
        $url->searchParams->delete('param2');
        $this->assertEquals('http://example.com/', $url->href);
        $this->assertEquals('', $url->search);
    }

    public function testDeleteNonExistentParamRemovesQuestionMark()
    {
        $url = new URL('http://example.com/?');
        $url->searchParams->delete('param1');
        $this->assertEquals('http://example.com/', $url->href);
        $this->assertEquals('', $url->search);
    }
}

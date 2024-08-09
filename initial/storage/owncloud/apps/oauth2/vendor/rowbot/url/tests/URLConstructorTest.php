<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-constructor.html
 */
class URLConstructorTest extends PHPUnit_Framework_TestCase
{
    protected $testData = null;

    public function getTestData()
    {
        if (!isset($this->testData)) {
            $data = json_decode(
                file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR . 'urltestdata.json'
                )
            );

            $this->testData = [];

            foreach ($data as $d) {
                $this->testData[] = [$d];
            }
        }

        return $this->testData;
    }

    /**
     * @dataProvider getTestData
     */
    public function testUrl($expected)
    {
        // Skip over comments in the json file.
        if (is_string($expected)) {
            return;
        }

        $shouldFail = property_exists($expected, 'failure') &&
            $expected->failure;

        if ($shouldFail) {
            $this->expectException(TypeError::class);
        }

        $base = $expected->base ? $expected->base : 'about:blank';
        $url = new URL($expected->input, $base);

        if ($shouldFail) {
            return;
        }

        $this->assertEquals($expected->href, $url->href, 'href');
        $this->assertEquals($expected->protocol, $url->protocol, 'protocol');
        $this->assertEquals($expected->username, $url->username, 'username');
        $this->assertEquals($expected->password, $url->password, 'password');
        $this->assertEquals($expected->host, $url->host, 'host');
        $this->assertEquals($expected->hostname, $url->hostname, 'hostname');
        $this->assertEquals($expected->port, $url->port, 'port');
        $this->assertEquals($expected->pathname, $url->pathname, 'pathname');
        $this->assertEquals($expected->search, $url->search, 'search');

        if (property_exists($expected, 'searchParams')) {
            $this->assertTrue((bool) $url->searchParams);
            $this->assertEquals($expected->searchParams, $url->searchParams->toString(), 'searchParams');
        }

        $this->assertEquals($expected->hash, $url->hash, 'hash');
    }

    public function test1()
    {
        $url = new URL('http://example.org/?a=b');
        $this->assertNotNull($url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertSame($searchParams, $url->searchParams);
    }

    /**
     * Test URL.searchParams updating, clearing.
     */
    public function test2()
    {
        $url = new URL('http://example.org/?a=b', 'about:blank');
        $this->assertNotNull($url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertEquals('a=b', $searchParams->toString());

        $searchParams->set('a', 'b');
        $this->assertEquals('a=b', $url->searchParams->toString());
        $this->assertEquals('?a=b', $url->search);
        $url->search = '';
        $this->assertEquals('', $url->searchParams->toString());
        $this->assertEquals('', $url->search);
        $this->assertEquals('', $searchParams->toString());
    }

    /**
     * @expectedException Rowbot\URL\Exception\TypeError
     */
    public function test3()
    {
        $urlString = 'http://example.org';
        $url = new URL($urlString, 'about:blank');
        $url->searchParams = new URLSearchParams($urlString);
    }

    public function test4()
    {
        $url = new URL('http://example.org/file?a=b&c=d');
        $this->assertInstanceOf(
            'Rowbot\URL\URLSearchParams',
            $url->searchParams
        );
        $searchParams = $url->searchParams;
        $this->assertEquals('?a=b&c=d', $url->search);
        $this->assertEquals('a=b&c=d', $searchParams->toString());

        // Test that setting 'search' propagates to the URL object's query
        // object
        $url->search = 'e=f&g=h';
        $this->assertEquals('?e=f&g=h', $url->search);
        $this->assertEquals('e=f&g=h', $url->searchParams->toString());

        // ...and same, but with a leading '?'
        $url->search = '?e=f&g=h';
        $this->assertEquals('?e=f&g=h', $url->search);
        $this->assertEquals('e=f&g=h', $url->searchParams->toString());

        // And in the other direction, altering searchParams propagates back
        // to 'search'
        $searchParams->append('i', ' j ');
        $this->assertEquals('?e=f&g=h&i=+j+', $url->search);
        $this->assertEquals('e=f&g=h&i=+j+', $url->searchParams->toString());
        $this->assertEquals(' j ', $searchParams->get('i'));

        $searchParams->set('e', 'updated');
        $this->assertEquals('?e=updated&g=h&i=+j+', $url->search);
        $this->assertEquals(
            'e=updated&g=h&i=+j+',
            $url->searchParams->__toString()
        );

        $url2 = new URL('http://example.org/file??a=b&c=d', 'about:blank');
        $this->assertEquals('??a=b&c=d', $url2->search);
        $this->assertEquals('%3Fa=b&c=d', $url2->searchParams->toString());

        $url2->href = 'http://example.org/file??a=b';
        $this->assertEquals('??a=b', $url2->search);
        $this->assertEquals('%3Fa=b', $url2->searchParams->toString());
    }
}

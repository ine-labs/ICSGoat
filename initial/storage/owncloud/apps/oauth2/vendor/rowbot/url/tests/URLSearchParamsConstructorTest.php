<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-constructor.html
 */
class URLSearchParamsConstructorTest extends PHPUnit_Framework_TestCase
{
    public function testBasicConstruction()
    {
        $params = new URLSearchParams();
        $this->assertEquals('', $params . '');
        $params = new URLSearchParams('');
        $this->assertEquals('', $params . '');
        $params = new URLSearchParams('a=b');
        $this->assertEquals('a=b', $params . '');
        $params = new URLSearchParams($params);
        $this->assertEquals('a=b', $params . '');
    }

    public function testConstructorNoArguments()
    {
        $params = new URLSearchParams();
        $this->assertEquals('', $params->toString());
    }

    public function testConstructorEmptyObject()
    {
        $params = new URLSearchParams(new \stdClass());
        $this->assertEquals('', (string) $params);
    }

    public function testConstructorString()
    {
        $params = new URLSearchParams('a=b');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('a'));
        $this->assertFalse($params->has('b'));
        $params = new URLSearchParams('a=b&c');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('a'));
        $this->assertTrue($params->has('c'));
        $params = new URLSearchParams('&a&&& &&&&&a+b=& c&m%c3%b8%c3%b8');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('a'));
        $this->assertTrue($params->has('a b'));
        $this->assertTrue($params->has(' '));
        $this->assertFalse($params->has('c'));
        $this->assertTrue($params->has(' c'));
        $this->assertTrue($params->has('møø'));
    }

    public function testConstructorObject()
    {
        $seed = new URLSearchParams('a=b&c=d');
        $params = new URLSearchParams($seed);
        $this->assertNotNull($params, 'message');
        $this->assertEquals('b', $params->get('a'));
        $this->assertEquals('d', $params->get('c'));
        $this->assertFalse($params->has('d'), 'message');
        // The name-value pairs are copied when  created; later, updates should
        // not be observable.
        $seed->append('e', 'f');
        $this->assertFalse($params->has('e'));
        $params->append('g', 'h');
        $this->assertFalse($seed->has('g'));
    }

    public function testParsePlusSign()
    {
        $params = new URLSearchParams('a=b+c');
        $this->assertEquals('b c', $params->get('a'));
        $params = new URLSearchParams('a+b=c');
        $this->assertEquals('c', $params->get('a b'));
    }

    public function testParseSpace()
    {
        $params = new URLSearchParams('a=b c');
        $this->assertEquals('b c', $params->get('a'));
        $params = new URLSearchParams('a b=c');
        $this->assertEquals('c', $params->get('a b'));
    }

    public function testParseSpacePercentEncoded()
    {
        $params = new URLSearchParams('a=b%20c');
        $this->assertEquals('b c', $params->get('a'));
        $params = new URLSearchParams('a%20b=c');
        $this->assertEquals('c', $params->get('a b'));
    }

    public function testParseNullByte()
    {
        $params = new URLSearchParams("a=b\0c");
        $this->assertEquals("b\0c", $params->get('a'));
        $params = new URLSearchParams("a\0b=c");
        $this->assertEquals('c', $params->get("a\0b"));
    }

    public function testParseNullBytePercentEncoded()
    {
        $params = new URLSearchParams('a=b%00c');
        $this->assertEquals("b\0c", $params->get('a'));
        $params = new URLSearchParams('a%00b=c');
        $this->assertEquals('c', $params->get("a\0b"));
    }

    public function testParseUnicodeCompositionSymbol()
    {
        $params = new URLSearchParams("a=b\xE2\x8E\x84");
        $this->assertEquals("b\xE2\x8E\x84", $params->get('a'));
        $params = new URLSearchParams("a\xE2\x8E\x84=c");
        $this->assertEquals('c', $params->get("a\xE2\x8E\x84"));
    }

    public function testParseUnicodeCompositionSymbolPercentEncoded()
    {
        $params = new URLSearchParams("a=b%E2%8E%84");
        $this->assertEquals("b\xE2\x8E\x84", $params->get('a'));
        $params = new URLSearchParams("a%E2%8E%84=c");
        $this->assertEquals('c', $params->get("a\xE2\x8E\x84"));
    }

    public function testParseUnicodePileOfPoo()
    {
        // $params = new URLSearchParams("a=b\u{1F4A9}c");
        // $this->assertEquals("b\u{1F4A9}c", $params->get('a'));
        // $params = new URLSearchParams("a\u{1F4A9}b=c");
        // $this->assertEquals('c', $params->get("a\u{1F4A9}b"));
        $params = new URLSearchParams("a=b\xF0\x9F\x92\xA9c");
        $this->assertEquals("b\xF0\x9F\x92\xA9c", $params->get('a'));
        $params = new URLSearchParams("a\xF0\x9F\x92\xA9b=c");
        $this->assertEquals('c', $params->get("a\xF0\x9F\x92\xA9b"));
    }

    public function testParseUnicodePileOfPooPercentEncoded()
    {
        $params = new URLSearchParams("a=b%f0%9f%92%a9c");
        // $this->assertEquals("b\u{1F4A9}c", $params->get('a'));
        $this->assertEquals("b\xF0\x9F\x92\xA9c", $params->get('a'));
        $params = new URLSearchParams("a%f0%9f%92%a9b=c");
        // $this->assertEquals('c', $params->get("a\u{1F4A9}b"));
        $this->assertEquals('c', $params->get("a\xF0\x9F\x92\xA9b"));
    }

    public function testSequenceOfSequences()
    {
        $params = new URLSearchParams([]);
        $this->assertNotNull($params);
        $params = new URLSearchParams([['a', 'b'], ['c', 'd']]);
        $this->assertEquals('b', $params->get('a'));
        $this->assertEquals('d', $params->get('c'));

        try {
            new URLSearchParams([[1]]);
            $this->assertTrue(false);
        } catch (TypeError $e) {
            $this->assertTrue(true);
        }

        try {
            new URLSearchParams([[1, 2, 3]]);
            $this->assertTrue(false);
        } catch (TypeError $e) {
            $this->assertTrue(true);
        }
    }

    public function getTestData()
    {
        $obj = new \stdClass();
        $obj->{"+"} = '%C2';

        $obj2 = new \stdClass();
        $obj2->c = 'x';
        $obj2->a = '?';

        $obj3 = new \stdClass();
        $obj3->{"a\0b"} = "42";
        // $obj3->{"c\u{D83D}"} = "23";
        // $obj3->{"d\u{1234}"} = "foo";
        $obj3->{"c\xF0\x9F"} = "23";
        $obj3->{"d\xE1\x88\xB4"} = "foo";

        return [
            ['input' => $obj, 'output' => [['+', "%C2"]]],
            [
                'input' => $obj2,
                'output' => [
                    ['c', 'x'],
                    ['a', '?']
                ]
            ],
            [
                'input' => [
                    ['c', 'x'],
                    ['a', '?']
                ],
                'output' => [
                    ['c', 'x'],
                    ['a', '?']
                ]
            ],
            [
                'input' => $obj3,
                'output' => [
                    ["a\0b", "42"],
                    // ["c\u{FFFD}", "23"],
                    // ["d\u{1234}", "foo"]
                    ["c\xEF\xBF\xBD", "23"],
                    ["d\xE1\x88\xB4", "foo"]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function test($input, $output)
    {
        $params = new URLSearchParams($input);
        $i = 0;

        foreach ($params as $param) {
            $this->assertEquals($output[$i++], $param);
        }
    }
}

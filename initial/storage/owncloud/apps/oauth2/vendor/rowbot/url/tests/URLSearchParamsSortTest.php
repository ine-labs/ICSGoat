<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-sort.html
 */
class URLSearchParamsSortTest extends PHPUnit_Framework_TestCase
{
    public function getTestData()
    {
        return [
            [
                'input' => 'z=b&a=b&z=a&a=a',
                'output' => [
                    ['a', 'b'],
                    ['a', 'a'],
                    ['z', 'b'],
                    ['z', 'a']
                ]
            ],
            [
                // 'input' => "\u{FFFD}=x&\u{FFFC}&\u{FFFD}=a",
                // 'output' => [
                //     ["\u{FFFC}", ''],
                //     ["\u{FFFD}", 'x'],
                //     ["\u{FFFD}", 'a']
                // ]
                'input' => "\xEF\xBF\xBD=x&\xEF\xBF\xBC&\xEF\xBF\xBD=a",
                'output' => [
                    ["\xEF\xBF\xBC", ''],
                    ["\xEF\xBF\xBD", 'x'],
                    ["\xEF\xBF\xBD", 'a']
                ]
            ],
            [
                'input' => 'ﬃ&🌈',
                'output' => [["🌈", ""], ["ﬃ", ""]]
            ],
            [
                // 'input' => "é&e\u{FFFD}&e\u{0301}",
                // 'output' => [
                //     ["e\u{0301}", ""],
                //     ["e\u{FFFD}", ""],
                //     ["é", ""]
                // ]
                'input' => "é&e\xEF\xBF\xBD&e\xCC\x81",
                'output' => [
                    ["e\xCC\x81", ""],
                    ["e\xEF\xBF\xBD", ""],
                    ["é", ""]
                ]
            ],
            [
                "input" => "z=z&a=a&z=y&a=b&z=x&a=c&z=w&a=d&z=v&a=e&z=u&a=f&z=t&a=g",
                "output" => [
                    ["a", "a"],
                    ["a", "b"],
                    ["a", "c"],
                    ["a", "d"],
                    ["a", "e"],
                    ["a", "f"],
                    ["a", "g"],
                    ["z", "z"],
                    ["z", "y"],
                    ["z", "x"],
                    ["z", "w"],
                    ["z", "v"],
                    ["z", "u"],
                    ["z", "t"]
                ]
            ],
            [
                "input" => "bbb&bb&aaa&aa=x&aa=y",
                "output" => [
                    ["aa", "x"],
                    ["aa", "y"],
                    ["aaa", ""],
                    ["bb", ""],
                    ["bbb", ""]
                ]
            ],
            [
                "input" => "z=z&=f&=t&=x",
                "output" => [["", "f"], ["", "t"], ["", "x"], ["z", "z"]]
            ],
            [
                "input" => "a🌈&a💩",
                "output" => [["a🌈", ""], ["a💩", ""]]
            ]
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testSort($input, $output)
    {
        $url = new URL('?' . $input, 'https://example/');
        $url->searchParams->sort();

        $params = new URLSearchParams($url->search);
        $i = 0;

        foreach ($params as $param) {
            $this->assertEquals($output[$i], $param);
            $i++;
        }
    }

    public function testSortingNonExistentParamsRemovesQuestionMark()
    {
        $url = new URL('http://example.com/?');
        $url->searchParams->sort();
        $this->assertEquals('http://example.com/', $url->href);
        $this->assertEquals('', $url->search);
    }
}

<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\URL;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-origin.html
 */
class URLOriginTest extends PHPUnit_Framework_TestCase
{
    protected $testData;

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
    public function testOrigin($expected)
    {
        // Skip comments and tests without origin
        if (is_string($expected) || !property_exists($expected, 'origin')) {
            return;
        }

        $base = $expected->base ? $expected->base : 'about:blank';
        $url = new URL($expected->input, $base);
        $this->assertEquals($expected->origin, $url->origin);
    }
}

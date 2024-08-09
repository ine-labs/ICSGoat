<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/toascii.window.js
 */
class ToASCIIWindowTest extends PHPUnit_Framework_TestCase
{
    protected $testData = null;

    public function getTestData()
    {
        if (!isset($this->testData)) {
            $data = json_decode(
                file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR . 'toascii.json'
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
    public function testURLContructor($hostTest)
    {
        // Skip comments
        if (is_string($hostTest)) {
            return;
        }

        if ($hostTest->output !== null) {
            $url = new URL('https://' . $hostTest->input . '/x');
            $this->assertEquals($hostTest->output, $url->host);
            $this->assertEquals($hostTest->output, $url->hostname);
            $this->assertEquals('/x', $url->pathname);
            $this->assertEquals(
                'https://' . $hostTest->output . '/x',
                $url->href
            );
        } else {
            $this->expectException(TypeError::class);
            $url = new URL($hostTest->input);
        }
    }

    /**
     * @dataProvider getTestData
     */
    public function testHostSetter($hostTest)
    {
        // Skip comments
        if (is_string($hostTest)) {
            return;
        }

        $url = new URL('https://x/x');
        $url->host = $hostTest->input;

        if ($hostTest->output !== null) {
            $this->assertEquals($hostTest->output, $url->host);
        } else {
            $this->assertEquals('x', $url->host);
        }
    }

    /**
     * @dataProvider getTestData
     */
    public function testHostnameSetter($hostTest)
    {
        // Skip comments
        if (is_string($hostTest)) {
            return;
        }

        $url = new URL('https://x/x');
        $url->hostname = $hostTest->input;

        if ($hostTest->output !== null) {
            $this->assertEquals($hostTest->output, $url->hostname);
        } else {
            $this->assertEquals('x', $url->hostname);
        }
    }
}

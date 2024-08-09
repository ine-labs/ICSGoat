<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/failure.html
 */
class FailureTest extends PHPUnit_Framework_TestCase
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
     * URL's constructor's first argument is tested by url-constructorTest. If a
     * URL fails to parse with any valid base, it must also fail to parse with
     * no base, i.e. when used as a base URL itself.
     *
     * @dataProvider getTestData
     */
    public function testURLContructor($test)
    {
        if (is_string($test)
            || (!property_exists($test, 'failure') || !$test->failure)
            || $test->base !== "about:blank"
        ) {
            return;
        }

        $this->expectException(TypeError::class);
        new URL("about:blank", $test->input);
    }
}

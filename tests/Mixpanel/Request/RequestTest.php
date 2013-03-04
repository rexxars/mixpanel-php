<?php
/**
 * PHP Mixpanel tracker
 *
 * Copyright (c) 2013 Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */

namespace Mixpanel\Request;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
abstract class RequestTest extends \PHPUnit_Framework_TestCase {
    /**
     * Method instance
     *
     * @var RequestInterface
     */
    protected $method;

    /**
     * Server URL
     *
     * @var string
     */
    protected $serverUrl;

    /**
     * Webserver PID
     *
     * @var integer
     */
    protected $pid;

    /**
     * Set up the internal server
     *
     */
    public function setUp() {
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('Request-tests requires php-5.4 to run');
        } else if (!defined('MIXPANEL_ENABLE_REQUEST_TESTS')
            || MIXPANEL_ENABLE_REQUEST_TESTS == false) {
            $this->markTestSkipped('MIXPANEL_ENABLE_REQUEST_TESTS must be set to true to run request-tests');
        } else if (!defined('MIXPANEL_REQUEST_TESTS_URL')
            || MIXPANEL_REQUEST_TESTS_URL == '') {
            $this->markTestSkipped('MIXPANEL_REQUEST_TESTS_URL must be set to a valid URL to run the request-tests');
        } else {
            $parts = parse_url(MIXPANEL_REQUEST_TESTS_URL);

            $this->serverUrl = rtrim(MIXPANEL_REQUEST_TESTS_URL, '/') . '/track';

            $this->pid = $this->startBuiltInHttpd(
                isset($parts['host']) ? $parts['host'] : 'localhost',
                isset($parts['port']) ? $parts['port'] : 80
            );

            usleep(250000);
        }
    }

    /**
     * Tear down the request method
     */
    public function tearDown() {
        $this->method = null;

        if ($this->pid) {
            exec('kill ' . $this->pid);
            $this->pid = null;
        }
    }

    /**
     * The request method must return boolean value when checking
     * for a supported setup
     *
     */
    public function testIsSupportedReturnsBooleanValue() {
        $this->assertInternalType('boolean', $this->method->isSupported());
    }

    /**
     * Test that an actual HTTP-request is sent (and received)
     *
     */
    public function testRequestIsSent() {
        $this->assertSame('1', $this->method->request($this->serverUrl, true));
    }

    /**
     * Starts a HTTPD-server allowing us to test outgoing requests
     *
     * @param string $host Hostname/IP to bind to (usually 127.0.0.1)
     * @param integer $port Port number to bind to
     * @return integer Returns PID of the HTTPD-process
     */
    protected function startBuiltInHttpd($host, $port) {
        $command = sprintf('php -S %s:%d %s >/dev/null 2>&1 & echo $!',
            $host,
            $port,
            HTTPD_SERVER_PATH
        );

        $output = array();
        exec($command, $output);

        return (int) $output[0];
    }

}
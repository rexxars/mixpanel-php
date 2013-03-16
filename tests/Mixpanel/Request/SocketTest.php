<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mixpanel\Request;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class SocketTest extends RequestTest {

    /**
     * Set up the request method
     *
     */
    public function setUp() {
        parent::setUp();

        $this->method = new Socket();
    }

    /**
     * Test that the request returns false if passed an invalid URL
     *
     * @covers Mixpanel\Request\Socket::request
     */
    public function testRequestFailsWithInvalidUrl() {
        $this->assertSame(false, $this->method->request('invalid.com', true));
        $this->assertSame(false, $this->method->request('http://127.0.127.0:33333/moo', true));
    }

}
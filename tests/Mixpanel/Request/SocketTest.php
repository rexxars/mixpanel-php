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
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

namespace Mixpanel;

use Mixpanel\Exception\InvalidArgumentException;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class TrackerTest extends \PHPUnit_Framework_TestCase {
    /**
     * Tracker instance
     *
     * @var Tracker
     */
    private $tracker;

    /**
     * Fake mixpanel project token
     *
     * @var string
     */
    private $token = 'some-token';

    /**
     * Fake distinct ID of the user
     *
     * @var string
     */
    private $distinctId = '31337';

    /**
     * Set up the tracker
     *
     * @covers Mixpanel\Tracker::__construct
     */
    public function setUp() {
        $this->tracker = new Tracker($this->token);
    }

    /**
     * Tear down the tracker
     */
    public function tearDown() {
        $this->tracker = null;
    }

    /**
     * The tracker must be able to set and get the project token
     *
     * @covers Mixpanel\Tracker::getToken
     * @covers Mixpanel\Tracker::setToken
     */
    public function testSettingAndGettingToken() {
        $this->assertSame($this->token, $this->tracker->getToken());
        $this->assertSame($this->tracker, $this->tracker->setToken('foobar'));
        $this->assertSame('foobar', $this->tracker->getToken());
    }

    /**
     * When trying to set the request method to something unknown,
     * the tracker must throw an exception
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Request method unknown: "none-existant"
     * @covers Mixpanel\Tracker::setRequestMethod
     */
    public function testSettingInvalidRequestMethodThrowsException() {
        $this->tracker->setRequestMethod('none-existant');
    }

    /**
     * The user must be able to configure the tracker to use a specific
     * request method
     *
     * @covers Mixpanel\Tracker::setRequestMethod
     * @covers Mixpanel\Tracker::getRequestMethod
     */
    public function testSettingAndGettingRequestMethod() {
        $this->assertSame(Tracker::METHOD_CURL, $this->tracker->setRequestMethod(Tracker::METHOD_CURL));
        $this->assertSame(Tracker::METHOD_CURL, $this->tracker->getRequestMethod());
    }

    /**
     * The tracker must be able to identify the user when passed a distinct ID
     *
     * @covers Mixpanel\Tracker::identify
     * @covers Mixpanel\Tracker::getDistinctId
     */
    public function testSettingAndGettingUserIdentity() {
        $this->assertSame($this->tracker, $this->tracker->identify($this->distinctId));
        $this->assertSame($this->distinctId, $this->tracker->getDistinctId());
    }

    /**
     * The tracker should by default not trust proxies
     *
     * @covers Mixpanel\Tracker::trustProxy
     */
    public function testTrustProxyShouldDefaultToFalse() {
        $this->assertSame(false, $this->tracker->trustProxy());
    }

    /**
     * The tracker must be able to change the trust proxy setting
     *
     * @covers Mixpanel\Tracker::trustProxy
     */
    public function testChangingTrustProxySetting() {
        $this->assertSame($this->tracker, $this->tracker->trustProxy(true));
        $this->assertSame(true, $this->tracker->trustProxy());
    }

}
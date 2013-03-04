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

use Mixpanel\Exception\InvalidArgumentException,
    ReflectionMethod,
    ReflectionProperty;

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
     * Expected cookie name
     *
     * @var string
     */
    private $cookieName = 'mp_some-token_mixpanel';

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

        unset($_COOKIE[$this->cookieName]);
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
     * The user must be able to configure the tracker to use a specific
     * request method
     *
     * @covers Mixpanel\Tracker::setRequestMethod
     * @covers Mixpanel\Tracker::getRequestMethod
     */
    public function testSettingAndGettingRequestMethod() {
        $method = new Request\CliCurl();

        $this->assertSame($method, $this->tracker->setRequestMethod($method));
        $this->assertSame($method, $this->tracker->getRequestMethod());
    }

    /**
     * The tracker must be able to auto-determine the best request method
     *
     * @covers Mixpanel\Tracker::setRequestMethod
     * @covers Mixpanel\Tracker::getRequestMethod
     */
    public function testAutoDetectingBestRequestMethod() {
        $this->assertInstanceOf('Mixpanel\Request\RequestInterface', $this->tracker->getRequestMethod());
    }

    /**
     * Calling getRequestMethod with no valid request methods should return false
     *
     * @covers Mixpanel\Tracker::setRequestMethod
     * @covers Mixpanel\Tracker::getRequestMethod
     * @covers Mixpanel\Tracker::setRequestMethodOrder
     */
    public function testShouldReturnFalseIfNoValidRequestMethodsExist() {
        $this->tracker->setRequestMethodOrder(array());
        $this->assertSame(false, $this->tracker->getRequestMethod());
    }

    /**
     * The tracker must be able to build the correct cookie name based on token
     *
     * @covers Mixpanel\Tracker::getCookieName
     */
    public function testCorrectCookieNameIsBuilt() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getCookieName');
        $method->setAccessible(true);

        $this->assertSame($this->cookieName, $method->invoke($this->tracker));
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
     * The tracker must be able to identify the user from cookie
     *
     * @covers Mixpanel\Tracker::getDistinctId
     */
    public function testGettingUserIdentityFromCookie() {
        $_COOKIE[$this->cookieName] = json_encode(array(
            'distinct_id' => $this->distinctId
        ));

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

    /**
     * The tracker must be able to extract the correct domain for cookies
     *
     * @covers Mixpanel\Tracker::getCookieDomain
     */
    public function testGetCookieDomainReturnsCorrectValues() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getCookieDomain');
        $method->setAccessible(true);

        $_SERVER['HTTP_HOST'] = 'api.mixpanel.com';
        $this->assertSame('.mixpanel.com', $method->invoke($this->tracker));

        $_SERVER['HTTP_HOST'] = 'cool.api.example.com';
        $this->assertSame('.example.com', $method->invoke($this->tracker));

        unset($_SERVER['HTTP_HOST']);
        $this->assertSame(null, $method->invoke($this->tracker));
    }

    /**
     * When __alias is part of the cookie, it should never be
     * included in returned array from getCookieProperties()
     *
     * @covers Mixpanel\Tracker::getCookieProperties
     */
    public function testAliasShouldNotBePartOfCookieProperties() {
        $_COOKIE[$this->cookieName] = json_encode(array(
            '__alias' => $this->distinctId,
            'age'     => 13,
        ));

        $method = new ReflectionMethod('Mixpanel\Tracker', 'getCookieProperties');
        $method->setAccessible(true);

        $params = $method->invoke($this->tracker);
        $this->assertArrayNotHasKey('__alias', $params);
    }

    /**
     * When no mixpanel-cookie is set, getCookieProperties()
     * should return an empty array
     *
     * @covers Mixpanel\Tracker::getCookieProperties
     */
    public function testGetCookiePropertiesShouldReturnEmptyArrayWhenCookieDoesNotExist() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getCookieProperties');
        $method->setAccessible(true);

        $params = $method->invoke($this->tracker);
        $this->assertEmpty($params);
    }

    /**
     * When no mixpanel-cookie is set, getCookieProperties()
     * should return an empty array
     *
     * @covers Mixpanel\Tracker::getCookieProperties
     */
    public function testGetCookiePropertiesShouldReturnEmptyArrayOnInvalidCookie() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getCookieProperties');
        $method->setAccessible(true);

        $_COOKIE[$this->cookieName] = '{"invalid":cookie"';

        $params = $method->invoke($this->tracker);
        $this->assertEmpty($params);
    }

    /**
     * Generating UUID should return different results every time
     *
     * @covers Mixpanel\Tracker::generateUuid
     */
    public function testGeneratingUuidShouldReturnDifferentResultEveryTime() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'generateUuid');
        $method->setAccessible(true);

        $uuids = array();
        for ($i = 0; $i < 100; $i++) {
            $uuid = $method->invoke($this->tracker);
            $this->assertNotContains($uuid, $uuids);

            $uuids[] = $uuid;
        }
    }

    /**
     * Make sure the tracker returns the user agent set in HTTP headers
     * if no user-agent is explicitly set
     *
     * @covers Mixpanel\Tracker::getClientUserAgent
     */
    public function testGetUserAgentWhenUserAgentExistsInHttpHeaders() {
        $ua = 'Some user agent';

        $_SERVER['HTTP_USER_AGENT'] = $ua;
        $this->assertSame($ua, $this->tracker->getClientUserAgent());

        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Make sure the tracker picks the explicitly set user-agent over
     * the ones populated through HTTP-headers
     *
     * @covers Mixpanel\Tracker::getClientUserAgent
     * @covers Mixpanel\Tracker::setClientUserAgent
     */
    public function testTrackerPicksExplicitlySetUserAgentOverHeaders() {
        $_SERVER['HTTP_USER_AGENT'] = 'Some user agent';
        $expectedUserAgent = 'I-set-this-one';

        $this->tracker->setClientUserAgent($expectedUserAgent);
        $this->assertSame($expectedUserAgent, $this->tracker->getClientUserAgent());

        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Test that we can identify operating system based on user agent
     *
     * @covers Mixpanel\Tracker::getClientUserAgent
     * @covers Mixpanel\Tracker::setClientUserAgent
     * @covers Mixpanel\Tracker::getClientOperatingSystem
     */
    public function testOperatingSystemCanBeDetected() {
        $uas = array(
            array('Android', 'Mozilla/5.0 (Linux; U; Android 4.0.4; en-gb; GT-I9300 Build/IMM76D) AppleWebKit/534.30 Mobile Safari/534.30'),
            array('Windows Mobile', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; SAMSUNG; SGH-i917)'),
            array('Windows', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 Chrome/24.0.1312.60 Safari/537.17'),
            array('Windows', 'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14'),
            array('Windows', 'Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1'),
            array('Linux',   'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 Chrome/17.0.963.66 Safari/535.11'),
            array('Linux',   'Opera/9.52 (X11; Linux x86_64; U; ru)'),
            array('Linux', 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:15.0) Gecko/20100101 Firefox/15.0.1'),
            array('iOS', 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 Mobile/7A341 Safari/528.16'),
            array('iOS', 'Mozilla/5.0 (iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 Mobile/7B314 Safari/531.21.10'),
            array('iOS', 'Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_3 like Mac OS X; ja-jp) AppleWebKit/533.17.9 Mobile/8J2 Safari/6533.18.5'),
            array('BlackBerry', 'Mozilla/5.0 (BlackBerry; U; BlackBerry 9900; en) AppleWebKit/534.11+ Mobile Safari/534.11+'),
            array('BlackBerry', 'Mozilla/5.0 (PlayBook; U; RIM Tablet OS 2.0.1; en-US) AppleWebKit/535.8+ Safari/535.8+'),
            array('BlackBerry', 'Mozilla/5.0 (BB10; Device) AppleWebKit/535.8+ Mobile Safari/534.11+'),
            array('Mac OS X', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ Safari/534.57.2'),
            array('Mac OS X', 'Opera/9.52 (Macintosh; PPC Mac OS X; U; fr)'),
            array('Mac OS X', 'Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4'),
            array('', 'Unknown'),
        );

        $method = new ReflectionMethod('Mixpanel\Tracker', 'getClientOperatingSystem');
        $method->setAccessible(true);

        foreach ($uas as $ua) {
            $this->tracker->setClientUserAgent($ua[1]);
            $this->assertSame($ua[0], $method->invoke($this->tracker));
        }
    }

    /**
     * Test that we can identify browser based on user agent
     *
     * @covers Mixpanel\Tracker::getClientUserAgent
     * @covers Mixpanel\Tracker::setClientUserAgent
     * @covers Mixpanel\Tracker::getClientBrowser
     */
    public function testBrowserCanBeDetected() {
        $uas = array(
            array('Opera Mini', 'Opera/9.80 (J2ME/MIDP; Opera Mini/9.80 (J2ME/22.478; U; en) Presto/2.5.25 Version/10.54'),
            array('Konqueror', 'Mozilla/5.0 (X11; Linux 3.5.4-1-ARCH i686; es) KHTML/4.9.1 (like Gecko) Konqueror/4.9'),
            array('Mozilla', 'Mozilla/5.0 (Windows; U; Win 9x 4.90; SG; rv:1.9.2.4) Gecko/20101104 Netscape/9.1.0285'),
            array('Android Mobile', 'Mozilla/5.0 (Linux; U; Android 4.0; xx-xx; GT-I9300 Build/IMM76D) AppleWebKit/534.30 Version/4.0 Mobile Safari/534.30'),
            array('Internet Explorer', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; SAMSUNG; SGH-i917)'),
            array('Chrome', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 Chrome/24.0.1312.60 Safari/537.17'),
            array('Opera', 'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14'),
            array('Firefox', 'Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1'),
            array('Chrome',   'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 Chrome/17.0.963.66 Safari/535.11'),
            array('Opera',   'Opera/9.52 (X11; Linux x86_64; U; ru)'),
            array('Firefox', 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:15.0) Gecko/20100101 Firefox/15.0.1'),
            array('Mobile Safari', 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 Mobile/7A341 Safari/528.16'),
            array('Mobile Safari', 'Mozilla/5.0 (iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 Mobile/7B314 Safari/531.21.10'),
            array('Mobile Safari', 'Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_3 like Mac OS X; ja-jp) AppleWebKit/533.17.9 Mobile/8J2 Safari/6533.18.5'),
            array('BlackBerry', 'Mozilla/5.0 (BlackBerry; U; BlackBerry 9900; en) AppleWebKit/534.11+ Mobile Safari/534.11+'),
            array('BlackBerry', 'Mozilla/5.0 (PlayBook; U; RIM Tablet OS 2.0.1; en-US) AppleWebKit/535.8+ Safari/535.8+'),
            array('BlackBerry', 'Mozilla/5.0 (BB10; Device) AppleWebKit/535.8+ Mobile Safari/534.11+'),
            array('Safari', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ Safari/534.57.2'),
            array('Opera', 'Opera/9.52 (Macintosh; PPC Mac OS X; U; fr)'),
            array('Firefox', 'Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4'),
            array('Internet Explorer', 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)'),
            array('Internet Explorer', 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)'),
            array('', 'Unknown'),
        );

        $method = new ReflectionMethod('Mixpanel\Tracker', 'getClientBrowser');
        $method->setAccessible(true);

        foreach ($uas as $ua) {
            $this->tracker->setClientUserAgent($ua[1]);
            $this->assertSame($ua[0], $method->invoke($this->tracker));
        }
    }



}
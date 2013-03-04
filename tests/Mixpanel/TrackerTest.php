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

use ReflectionMethod,
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
     * Request method used for testing
     *
     * @var RequestInterface
     */
    private $requestMethod;

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

    /**
     * Test that we can build a correct set of default properties
     *
     * @covers Mixpanel\Tracker::getDefaultProperties
     * @covers Mixpanel\Tracker::setClientUserAgent
     * @covers Mixpanel\Tracker::getClientBrowser
     * @covers Mixpanel\Tracker::getClientOperatingSystem
     */
    public function testDefaultPropertiesAreReturnedCorrectly() {
        $ua = 'Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4';

        $this->tracker->setClientUserAgent($ua);

        $method = new ReflectionMethod('Mixpanel\Tracker', 'getDefaultProperties');
        $method->setAccessible(true);

        $this->assertSame(array(
            '$os'      => 'Mac OS X',
            '$browser' => 'Firefox',
            'mp_lib'   => 'php',
        ), $method->invoke($this->tracker));
    }

    /**
     * Test that we can identify browser based on user agent
     *
     * @covers Mixpanel\Tracker::setClientUserAgent
     * @covers Mixpanel\Tracker::getClientBrowser
     * @covers Mixpanel\Tracker::isBlockedUserAgent
     */
    public function testBlockedBrowsersAreDetected() {
        $uas = array(
            array(true,  'Mozilla/5.0 (en-us) AppleWebKit/525.13 (KHTML, like Gecko; Google Web Preview) Version/3.1 Safari/525.13 '),
            array(true,  'Baiduspider+(+http://www.baidu.com/search/spider.htm)'),
            array(true,  'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)'),
            array(false, 'Mozilla/5.0 (X11; Linux 3.5.4-1-ARCH i686; es) KHTML/4.9.1 (like Gecko) Konqueror/4.9'),
            array(false, 'Mozilla/5.0 (Windows; U; Win 9x 4.90; SG; rv:1.9.2.4) Gecko/20101104 Netscape/9.1.0285'),
            array(false, 'Mozilla/5.0 (Linux; U; Android 4.0; xx-xx; GT-I9300 Build/IMM76D) AppleWebKit/534.30 Version/4.0 Mobile Safari/534.30'),
        );

        $method = new ReflectionMethod('Mixpanel\Tracker', 'isBlockedUserAgent');
        $method->setAccessible(true);

        foreach ($uas as $ua) {
            $this->tracker->setClientUserAgent($ua[1]);
            $this->assertSame($ua[0], $method->invoke($this->tracker));
        }
    }

    /**
     * Test that we can identify the clients IP-address
     *
     * @covers Mixpanel\Tracker::getClientIp
     */
    public function testReturnsFirstIpFromProxyHeader() {
        $this->tracker->trustProxy(true);

        $method = new ReflectionMethod('Mixpanel\Tracker', 'getClientIp');
        $method->setAccessible(true);

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '4.4.4.4, 8.8.8.8';
        $this->assertSame('4.4.4.4', $method->invoke($this->tracker));

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2001:db8:85a3::8a2e:370:7334, 2607:f0d0:1002:51::4';
        $this->assertSame('2001:db8:85a3::8a2e:370:7334', $method->invoke($this->tracker));
    }

    /**
     * Test that we can identify the clients IP-address based on server info
     *
     * @covers Mixpanel\Tracker::getClientIp
     */
    public function testReturnsRemoteAddressIfWeDontTrustProxies() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getClientIp');
        $method->setAccessible(true);

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '4.4.4.4, 8.8.8.8';
        $_SERVER['REMOTE_ADDR'] = '2001:db8:85a3::8a2e:370:7334';
        $this->assertSame('2001:db8:85a3::8a2e:370:7334', $method->invoke($this->tracker));
    }

    /**
     * Test that the tracker returns 127.0.0.1 if no IP can be found
     *
     * @covers Mixpanel\Tracker::getClientIp
     */
    public function testReturnsHostnameIfNoIpIsFound() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getClientIp');
        $method->setAccessible(true);

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);

        $this->assertSame('127.0.0.1', $method->invoke($this->tracker));
    }

    /**
     * Test that storeUuid() sends a Set-Cookie header if no cookie exists
     *
     * @covers Mixpanel\Tracker::storeUuid
     * @covers Mixpanel\Tracker::setCookieWriter
     * @covers Mixpanel\Tracker::getCookieWriter
     */
    public function testStoreUuidSendsSetCookieHeaderIfNoCookieAlreadySet() {
        $storeUuid = new ReflectionMethod('Mixpanel\Tracker', 'storeUuid');
        $storeUuid->setAccessible(true);

        $cookieWriter = $this->getMock('Mixpanel\CookieWriter');
        $cookieWriter->expects($this->once())->method('canSend')->will($this->returnValue(true));
        $cookieWriter->expects($this->once())->method('setCookie')->will($this->returnValue(true));

        $setCookieWriter = new ReflectionMethod('Mixpanel\Tracker', 'setCookieWriter');
        $setCookieWriter->setAccessible(true);
        $setCookieWriter->invoke($this->tracker, $cookieWriter);

        $this->tracker->identify($this->distinctId);
        $this->assertSame(true, $storeUuid->invoke($this->tracker));
    }

    /**
     * Test that storeUuid() returns false if headers already sent
     *
     * @covers Mixpanel\Tracker::storeUuid
     * @covers Mixpanel\Tracker::setCookieWriter
     * @covers Mixpanel\Tracker::getCookieWriter
     */
    public function testStoreUuidReturnsFalseIfHeadersSent() {
        $storeUuid = new ReflectionMethod('Mixpanel\Tracker', 'storeUuid');
        $storeUuid->setAccessible(true);

        $cookieWriter = $this->getMock('Mixpanel\CookieWriter');
        $cookieWriter->expects($this->once())->method('canSend')->will($this->returnValue(false));

        $setCookieWriter = new ReflectionMethod('Mixpanel\Tracker', 'setCookieWriter');
        $setCookieWriter->setAccessible(true);
        $setCookieWriter->invoke($this->tracker, $cookieWriter);

        $this->tracker->identify($this->distinctId);
        $this->assertSame(false, $storeUuid->invoke($this->tracker));
    }

    /**
     * Test that storeUuid() returns false if cookie is already set
     *
     * @covers Mixpanel\Tracker::storeUuid
     * @covers Mixpanel\Tracker::setCookieWriter
     * @covers Mixpanel\Tracker::getCookieWriter
     */
    public function testStoreUuidReturnsFalseIfCookieAlreadySet() {
        $storeUuid = new ReflectionMethod('Mixpanel\Tracker', 'storeUuid');
        $storeUuid->setAccessible(true);

        $cookieWriter = $this->getMock('Mixpanel\CookieWriter');
        $cookieWriter->expects($this->never())->method('canSend');
        $cookieWriter->expects($this->never())->method('setCookie');

        $setCookieWriter = new ReflectionMethod('Mixpanel\Tracker', 'setCookieWriter');
        $setCookieWriter->setAccessible(true);
        $setCookieWriter->invoke($this->tracker, $cookieWriter);

        $_COOKIE[$this->cookieName] = 'some-value';

        $this->tracker->identify($this->distinctId);
        $this->assertSame(false, $storeUuid->invoke($this->tracker));
    }

    /**
     * Test that we can get a default cookie writer if none is set
     *
     * @covers Mixpanel\Tracker::getCookieWriter
     */
    public function testCanInstantiateCookieWriterIfNoneIsSet() {
        $getCookieWriter = new ReflectionMethod('Mixpanel\Tracker', 'getCookieWriter');
        $getCookieWriter->setAccessible(true);

        $this->assertInstanceOf('Mixpanel\CookieWriter', $getCookieWriter->invoke($this->tracker));
    }

    /**
     * Test that we can get a default uuid generator if none is set
     *
     * @covers Mixpanel\Tracker::getUuidGenerator
     * @covers Mixpanel\Tracker::setUuidGenerator
     */
    public function testCanInstantiateUuidGeneratorIfNoneIsSet() {
        $this->assertInstanceOf('Mixpanel\Uuid\GeneratorInterface', $this->tracker->getUuidGenerator());
    }

    /**
     * Test that we can set and get a specific UUID generator
     *
     * @covers Mixpanel\Tracker::getUuidGenerator
     * @covers Mixpanel\Tracker::setUuidGenerator
     */
    public function testCanGetAndSetUuidGenerator() {
        $generator = new Uuid\Generator();

        $this->assertEquals($this->tracker, $this->tracker->setUuidGenerator($generator));
        $this->assertEquals($generator, $this->tracker->getUuidGenerator());
    }

    /**
     * Test that we can set and get a specific IP
     *
     * @covers Mixpanel\Tracker::setClientIp
     * @covers Mixpanel\Tracker::getClientIp
     */
    public function testCanGetAndSetClientIp() {
        $ip = '192.168.1.1';
        $this->assertEquals($this->tracker, $this->tracker->setClientIp($ip));
        $this->assertEquals($ip, $this->tracker->getClientIp());
    }

    /**
     * Test that track() returns false if no token is set
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackReturnsFalseIfNoTokenIsSet() {
        $this->tracker = new Tracker();
        $this->assertSame(false, $this->tracker->track('Some event'));
    }

    /**
     * Test that track() calls the request method with bare essentials
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackRequestsTrackEndpoint() {
        $this->initMockUuidGenerator();
        $this->initMockRequester();

        $this->assertSame(true, $this->tracker->track('Some event'));
    }

    /**
     * Test that track() calls the request method when a specific IP has been set
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackWithSpecifiedIp() {
        $this->initMockUuidGenerator();
        $this->initMockRequester();
        $this->tracker->setClientIp('192.168.1.77');

        $this->assertSame(true, $this->tracker->track('Some event'));
    }

    /**
     * Test that track() calls the request method when a user has been identified
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackWithSpecifiedUser() {
        $this->initMockRequester();
        $this->tracker->identify($this->distinctId);

        $this->assertSame(true, $this->tracker->track('Some event'));
    }

    /**
     * Test that alias() returns false if no user-ID is set
     *
     * @covers Mixpanel\Tracker::alias
     */
    public function testAliasWithNoDistinctId() {
        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->never())
                  ->method('request');

        $this->tracker->setRequestMethod($requester);

        $this->assertSame(false, $this->tracker->alias('UserAlias'));
    }

    /**
     * Test that alias() sends actual request
     *
     * @covers Mixpanel\Tracker::alias
     * @covers Mixpanel\Tracker::track
     */
    public function testAliasWithDistinctId() {
        $this->initMockRequester();
        $this->tracker->identify($this->distinctId);
        $this->assertSame(true, $this->tracker->alias('UserAlias'));
    }

    private function initMockUuidGenerator() {
        $uuidGenerator = $this->getMock('Mixpanel\Uuid\GeneratorInterface');
        $uuidGenerator->expects($this->once())->method('generate')->will($this->returnValue('uuid'));
        $this->tracker->setUuidGenerator($uuidGenerator);
    }

    private function initMockRequester() {
        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->once())
                  ->method('request')
                  ->with($this->matchesRegularExpression('#^http://api.mixpanel.com/track#'))
                  ->will($this->returnValue(true));

        $this->tracker->setRequestMethod($requester);
    }

}
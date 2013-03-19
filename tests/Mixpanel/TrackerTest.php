<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mixpanel;

use ReflectionMethod,
    ReflectionProperty,
    Mixpanel\Constraint\PropertyListContainsKeyValuePair;

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
     * Request method used for testing
     *
     * @var RequestInterface
     */
    private $requestMethod;

    /**
     * Set up the tracker
     *
     * @covers Mixpanel\Tracker::__construct
     * @covers Mixpanel\Tracker::setConfig
     */
    public function setUp() {
        $this->tracker = new Tracker($this->token, array(
            'storeGoogle' => true
        ));
    }

    /**
     * Tear down the tracker
     */
    public function tearDown() {
        $this->tracker = null;
    }

    /**
     * Make sure setConfig actually sets the config
     *
     * @covers Mixpanel\Tracker::setConfig
     */
    public function testSetConfigOverridesDefaultValues() {
        $property = new ReflectionProperty('Mixpanel\Tracker', 'config');
        $property->setAccessible(true);

        $this->tracker->setConfig(array(
            'storeGoogle'  => false,
            'saveReferrer' => false,
            'test'         => true,
        ));

        $config = $property->getValue($this->tracker);

        $this->assertFalse($config['storeGoogle']);
        $this->assertFalse($config['saveReferrer']);
        $this->assertTrue($config['test']);
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
        $this->assertFalse($this->tracker->getRequestMethod());
    }

    /**
     * The tracker must be able to identify the user when passed a distinct ID
     *
     * @covers Mixpanel\Tracker::identify
     * @covers Mixpanel\Tracker::getDistinctId
     */
    public function testSettingAndGettingUserIdentity() {
        // Should call setUserUuid on data store
        $store = $this->initMockDataStore(false);
        $store->expects($this->once())
              ->method('setUserUuid')
              ->with($this->equalTo($this->distinctId))
              ->will($this->returnValue($store));

        $this->assertSame($this->tracker, $this->tracker->identify($this->distinctId));
        $this->assertSame($this->distinctId, $this->tracker->getDistinctId());
    }

    /**
     * The tracker must be able to identify the user from data store
     *
     * @covers Mixpanel\Tracker::getDistinctId
     */
    public function testGettingUserIdentityFromDataStore() {
        $store = $this->initMockDataStore();
        $store->expects($this->once())
              ->method('get')
              ->with($this->equalTo('distinct_id'))
              ->will($this->returnValue($this->distinctId));

        $this->assertSame($this->distinctId, $this->tracker->getDistinctId());
    }

    /**
     * The tracker must not create a distinct ID if told not to
     *
     * @covers Mixpanel\Tracker::getDistinctId
     */
    public function testDontGenerateUuidIfToldNotTo() {
        $store = $this->initMockDataStore();
        $store->expects($this->once())
              ->method('get')
              ->with($this->equalTo('distinct_id'))
              ->will($this->returnValue(false));

        $this->assertFalse(false, $this->tracker->getDistinctId(false));
    }

    /**
     * The tracker must create a distinct ID if none is found
     * in data storage or set explicitly
     *
     * @covers Mixpanel\Tracker::getDistinctId
     */
    public function testGenerateUuidIfNoneIsSet() {
        $store = $this->initMockDataStore();
        $store->expects($this->once())
              ->method('get')
              ->with($this->equalTo('distinct_id'))
              ->will($this->returnValue(false));

        $this->assertRegExp('#[a-f0-9-]{8,}#', $this->tracker->getDistinctId());
    }

    /**
     * The tracker must be able to name-tag a user, setting the passed value in
     * data store for future tracking-calls
     *
     * @covers Mixpanel\Tracker::nameTag
     */
    public function testNameTagShouldRegisterValue() {
        $nameTag = 'Espen Hovlandsdal';
        $store = $this->initMockDataStore(false);
        $store->expects($this->once())
              ->method('set')
              ->with($this->equalTo('mp_name_tag'), $this->equalTo($nameTag))
              ->will($this->returnValue(false));

        $this->assertSame($this->tracker, $this->tracker->nameTag($nameTag));
    }

    /**
     * The tracker should by default not trust proxies
     *
     * @covers Mixpanel\Tracker::trustProxy
     */
    public function testTrustProxyShouldDefaultToFalse() {
        $this->assertFalse($this->tracker->trustProxy());
    }

    /**
     * The tracker must be able to change the trust proxy setting
     *
     * @covers Mixpanel\Tracker::trustProxy
     */
    public function testChangingTrustProxySetting() {
        $this->assertSame($this->tracker, $this->tracker->trustProxy(true));
        $this->assertTrue($this->tracker->trustProxy());
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
     * Test that we can identify device based on user agent
     *
     * @covers Mixpanel\Tracker::setClientUserAgent
     * @covers Mixpanel\Tracker::getClientDevice
     */
    public function testDeviceCanBeDetected() {
        $uas = array(
            array('', 'Opera/9.80 (J2ME/MIDP; Opera Mini/9.80 (J2ME/22.478; U; en) Presto/2.5.25 Version/10.54'),
            array('', 'Mozilla/5.0 (X11; Linux 3.5.4-1-ARCH i686; es) KHTML/4.9.1 (like Gecko) Konqueror/4.9'),
            array('', 'Mozilla/5.0 (Windows; U; Win 9x 4.90; SG; rv:1.9.2.4) Gecko/20101104 Netscape/9.1.0285'),
            array('Android', 'Mozilla/5.0 (Linux; Android 4.0.4; Galaxy Nexus Build/IMM76B) AppleWebKit/535.19 Chrome/18.0.1025.133 Mobile Safari/535.19'),
            array('Android', 'Mozilla/5.0 (Linux; U; Android 4.0; xx-xx; GT-I9300 Build/IMM76D) AppleWebKit/534.30 Version/4.0 Mobile Safari/534.30'),
            array('Windows Phone', 'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; SAMSUNG; SGH-i917)'),
            array('iPhone', 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 Mobile/7A341 Safari/528.16'),
            array('iPad', 'Mozilla/5.0 (iPad; CPU OS 5_1 like Mac OS X) AppleWebKit/534.46 Version/5.1 Mobile/9B176 Safari/7534.48.3'),
            array('iPod Touch', 'Mozila/5.0 (iPod; U; CPU like Mac OS X; en) AppleWebKit/420.1 Version/3.0 Mobile/3A101a Safari/419.3'),
            array('iPhone', 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 5_1_1 like Mac OS X; en) AppleWebKit/534.46.0 CriOS/19.0.1084.60 Mobile/9B206 Safari/7534.48.3'),
            array('BlackBerry', 'Mozilla/5.0 (BlackBerry; U; BlackBerry 9900; en) AppleWebKit/534.11+ Mobile Safari/534.11+'),
            array('BlackBerry', 'Mozilla/5.0 (PlayBook; U; RIM Tablet OS 2.0.1; en-US) AppleWebKit/535.8+ Safari/535.8+'),
            array('BlackBerry', 'Mozilla/5.0 (BB10; Device) AppleWebKit/535.8+ Mobile Safari/534.11+'),
            array('Android', 'Mozilla/5.0(Android verison); Linux version Gecko/20120123 Firefox/10.0 Fennec/10.0'),
            array('', 'Unknown'),
        );

        $method = new ReflectionMethod('Mixpanel\Tracker', 'getClientDevice');
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
     * Test that we can get a default data storage adapter if none is set
     *
     * @runInSeparateProcess
     * @covers Mixpanel\Tracker::getDataStorage
     * @covers Mixpanel\Tracker::setDataStorage
     */
    public function testCanInstantiateDataStorageIfNoneIsSet() {
        $getDataStorage = new ReflectionMethod('Mixpanel\Tracker', 'getDataStorage');
        $getDataStorage->setAccessible(true);

        $this->assertInstanceOf('Mixpanel\DataStorage\Cookie', $getDataStorage->invoke($this->tracker));
    }

    /**
     * Test that we can set and get a data storage adapter
     *
     * @covers Mixpanel\Tracker::setDataStorage
     * @covers Mixpanel\Tracker::getDataStorage
     */
    public function testCanSetAndGetDataStorageAdapter() {
        $store = $this->getMock('Mixpanel\DataStorage\StorageInterface');

        $this->tracker->setDataStorage($store);

        $getDataStorage = new ReflectionMethod('Mixpanel\Tracker', 'getDataStorage');
        $getDataStorage->setAccessible(true);

        $this->assertEquals($store, $getDataStorage->invoke($this->tracker));
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
     * Test that track() returns false when client is using a blocked user agent
     *
     * @covers Mixpanel\Tracker::setClientUserAgent
     * @covers Mixpanel\Tracker::isBlockedUserAgent
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackReturnsFalseOnBlockedUserAgent() {
        $ua = 'Mozilla/5.0 (en-us) AppleWebKit/525.13 (KHTML, like Gecko; Google Web Preview) Version/3.1 Safari/525.13 ';

        $this->tracker->setClientUserAgent($ua);
        $this->assertFalse($this->tracker->track('Some event'));
    }

    /**
     * Test that track() returns false if no token is set
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackReturnsFalseIfNoTokenIsSet() {
        $this->tracker = new Tracker();
        $this->assertFalse($this->tracker->track('Some event'));
    }

    /**
     * Test that track() calls the request method with bare essentials
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackRequestsTrackEndpoint() {
        $this->initMockUuidGenerator();
        $this->initMockRequester();
        $this->initMockDataStore();

        $this->assertTrue($this->tracker->track('Some event'));
    }

    /**
     * Test that track() includes test=1 in URL if test-flag is set in config
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackRequestContainsTestParamInUrlIfConfigFlagSet() {
        $this->tracker->setConfig(array(
            'test' => true
        ));

        $this->initMockUuidGenerator();
        $this->initMockDataStore();

        $requester = $this->initMockRequester();
        $requester->expects($this->once())
                  ->method('request')
                  ->with($this->matchesRegularExpression('#[&?]test=1(&|$)#'))
                  ->will($this->returnValue(true));

        $this->assertTrue($this->tracker->track('Some event'));
    }

    /**
     * Test that track() calls the request method when a specific IP has been set
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackWithSpecifiedIp() {
        $this->initMockUuidGenerator();
        $this->initMockRequester();
        $this->initMockDataStore();
        $this->tracker->setClientIp('192.168.1.77');

        $this->assertTrue($this->tracker->track('Some event'));
    }

    /**
     * Test that track() calls the request method when a user has been identified
     *
     * @covers Mixpanel\Tracker::track
     */
    public function testTrackWithSpecifiedUser() {
        $this->initMockRequester();
        $this->initMockDataStore();
        $this->tracker->identify($this->distinctId);

        $this->assertTrue($this->tracker->track('Some event'));
    }

    /**
     * Test that trackPageView() sets correct URL and sends request
     *
     * @covers Mixpanel\Tracker::track
     * @covers Mixpanel\Tracker::trackPageView
     */
    public function testTrackPageViewSendsGivenUrl() {
        $this->initMockDataStore();

        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->once())
                  ->method('request')
                  ->with(new PropertyListContainsKeyValuePair('mp_page', 'http://tech.vg.no/'))
                  ->will($this->returnValue(true));

        $this->tracker->setRequestMethod($requester);

        $this->tracker->trackPageView('http://tech.vg.no/');
    }

    /**
     * Test that trackPageView() resolves correct URL and sends request
     *
     * @covers Mixpanel\Tracker::track
     * @covers Mixpanel\Tracker::trackPageView
     */
    public function testTrackPageViewResolvesCorrectUrl() {
        $this->initMockDataStore();

        $_SERVER['HTTP_HOST'] = 'tech.vg.no';
        $_SERVER['REQUEST_URI'] = '/some/page';

        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->once())
                  ->method('request')
                  ->with(new PropertyListContainsKeyValuePair('mp_page', 'http://tech.vg.no/some/page'))
                  ->will($this->returnValue(true));

        $this->tracker->setRequestMethod($requester);

        $this->tracker->trackPageView();

        unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
    }

    /**
     * Test that trackPageView() resolves correct domain and protocol,
     * appends passed page URL and sends tracking request
     *
     * @covers Mixpanel\Tracker::track
     * @covers Mixpanel\Tracker::trackPageView
     */
    public function testTrackPageViewResolvesDomainAndProtocol() {

        $this->initMockDataStore();

        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'github.com';
        $_SERVER['REQUEST_URI'] = '/rexxars/mixpanel-php';

        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->once())
                  ->method('request')
                  ->with(new PropertyListContainsKeyValuePair('mp_page', 'https://github.com/rexxars/mixpanel-php'))
                  ->will($this->returnValue(true));

        $this->tracker->setRequestMethod($requester);

        $this->tracker->trackPageView();

        unset($_SERVER['HTTPS'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);
    }

    /**
     * Test that trackPageView() sends passed URLs untouched to mixpanel
     *
     * @covers Mixpanel\Tracker::track
     * @covers Mixpanel\Tracker::trackPageView
     */
    public function testTrackPageViewSendsPassedUrlsUntouched() {
        $this->initMockDataStore();

        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->once())
                  ->method('request')
                  ->with(new PropertyListContainsKeyValuePair('mp_page', '/rexxars/mixpanel-php'))
                  ->will($this->returnValue(true));

        $this->tracker->setRequestMethod($requester);

        $this->tracker->trackPageView('/rexxars/mixpanel-php');
    }

    /**
     * Test that trackPageView() passes the URL untouched through if complete
     *
     * @covers Mixpanel\Tracker::track
     * @covers Mixpanel\Tracker::trackPageView
     */
    public function testTrackPageViewDoesNotAlterFullUrl() {

        $this->initMockDataStore();

        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->once())
                  ->method('request')
                  ->with(new PropertyListContainsKeyValuePair('mp_page', 'http://rexxars.com/test.html'))
                  ->will($this->returnValue(true));

        $this->tracker->setRequestMethod($requester);

        $this->tracker->trackPageView('http://rexxars.com/test.html');
    }

    /**
     * Test that alias() returns false if no user-ID is set
     *
     * @covers Mixpanel\Tracker::alias
     */
    public function testAliasWithNoDistinctId() {
        $this->initMockDataStore(false);

        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->never())
                  ->method('request');

        $this->tracker->setRequestMethod($requester);

        $this->assertFalse($this->tracker->alias('UserAlias'));
    }

    /**
     * Test that alias() sends actual request
     *
     * @covers Mixpanel\Tracker::alias
     * @covers Mixpanel\Tracker::track
     */
    public function testAliasWithDistinctId() {
        $this->initMockRequester();
        $this->initMockDataStore();
        $this->tracker->identify($this->distinctId);
        $this->assertTrue($this->tracker->alias('SomeUserAlias'));
    }

    /**
     * Test that we can get the correct search engine names from a list of URLs
     *
     * @covers Mixpanel\Tracker::getSearchEngineFromUrl
     */
    public function testGetSearchEngineFromUrl() {
        $urls = array(
            array('google',     'https://www.google.com/search?channel=fs&q=mixpanel-php&ie=utf-8&oe=utf-8'),
            array('google',     'https://www.google.no/search?channel=fs&q=mixpanel-php&ie=utf-8&oe=utf-8'),
            array('google',     'http://www.google.no/search?hl=no&q=mixpanel-php&gbv=1&um=1&ie=UTF-8&tbm=isch&source=og&sa=N&tab=wi'),
            array('yahoo',      'http://no.search.yahoo.com/search;_ylt=whoa_;_ylc=thatsalonguglyurl--?p=mixpanel-php&toggle=1&cop=mss&ei=UTF-8&fr=yfp-t-734'),
            array('yahoo',      'http://search.yahoo.com/search;_ylt=AvgXSzoOCXENsec4WkmdNPebvZx4?p=mixpanel-php&toggle=1&cop=mss&ei=UTF-8&fr=yfp-t-900'),
            array('yahoo',      'http://search.yahoo.co.jp/search?p=mixpanel-php&search.x=1&fr=top_ga1_sa&tid=top_ga1_sa&ei=UTF-8&aq=&oq=miux'),
            array('bing',       'http://www.bing.com/search?q=mixpanel-php&go=&qs=n&form=QBLH&filt=all&pq=mixpanel-php&sc=0-6&sp=-1&sk='),
            array('duckduckgo', 'https://duckduckgo.com/?q=mixpanel-php'),
            array(null,         'http://www.kvasir.no/alle?q=mixpanel-php'),
            array(null,          null),
        );

        $method = new ReflectionMethod('Mixpanel\Tracker', 'getSearchEngineFromUrl');
        $method->setAccessible(true);

        foreach ($urls as $url) {
            $this->assertSame($url[0], $method->invoke($this->tracker, $url[1]), 'URL: ' . $url[1]);
        }
    }

    public function testUpdateSearchKeywordDoesNotRegisterPropertiesWhenSearchEngineCouldNotBeDetermined() {

    }

    /**
     * GetReferrer() should return Referer header if set, false otherwise
     *
     * @covers Mixpanel\Tracker::getReferrer
     */
    public function testGetReferrerReturnsCorrectValues() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getReferrer');
        $method->setAccessible(true);

        $url = 'http://tech.vg.no/';
        $_SERVER['HTTP_REFERER'] = $url;
        $this->assertSame($url, $method->invoke($this->tracker));

        unset($_SERVER['HTTP_REFERER']);
        $this->assertFalse($method->invoke($this->tracker));
    }

    /**
     * GetReferringDomain() should return empty string if it could not be determined,
     * referring domain otherwise
     *
     * @covers Mixpanel\Tracker::getReferrer
     * @covers Mixpanel\Tracker::getReferringDomain
     */
    public function testGetReferringDomainReturnsCorrectValues() {
        $method = new ReflectionMethod('Mixpanel\Tracker', 'getReferringDomain');
        $method->setAccessible(true);

        $_SERVER['HTTP_REFERER'] = 'http://tech.vg.no/';
        $this->assertSame('tech.vg.no', $method->invoke($this->tracker));

        $_SERVER['HTTP_REFERER'] = 'https://mixpanel.com/some/path/';
        $this->assertSame('mixpanel.com', $method->invoke($this->tracker));

        $_SERVER['HTTP_REFERER'] = 'https://mixpanel.com';
        $this->assertSame('mixpanel.com', $method->invoke($this->tracker));

        $_SERVER['HTTP_REFERER'] = 'some invalid url';
        $this->assertSame('', $method->invoke($this->tracker));

        unset($_SERVER['HTTP_REFERER']);
        $this->assertSame('', $method->invoke($this->tracker));
    }

    /**
     * register() should call set() on the data storage layer the same
     * number of times as we pass properties to it
     *
     * @covers Mixpanel\Tracker::register
     */
    public function testRegisterCallsDataStorageSetCorrectNumberOfTimes() {
        $data = array(
            'foo' => 'bar',
            'mix' => 'panel',
            'rex' => 'xars',
        );

        $store = $this->initMockDataStore(false);
        $store->expects($this->exactly(count($data)))
                  ->method('set')
                  ->will($this->returnValue($store));

        $this->assertEquals($this->tracker, $this->tracker->register($data));
    }

    /**
     * registerOnce() should call add() on the data storage layer the same
     * number of times as we pass properties to it
     *
     * @covers Mixpanel\Tracker::registerOnce
     */
    public function testRegisterOnceCallsDataStorageAddCorrectNumberOfTimes() {
        $data = array(
            'foo' => 'bar',
            'mix' => 'panel',
            'rex' => 'xars',
        );

        $store = $this->initMockDataStore(false);
        $store->expects($this->exactly(count($data)))
                  ->method('add')
                  ->will($this->returnValue($store));

        $this->assertEquals($this->tracker, $this->tracker->registerOnce($data));
    }

    /**
     * unregister() should call delete() on the data storage layer
     *
     * @covers Mixpanel\Tracker::unregister
     */
    public function testUnregisterCallsDataStorageDelete() {
        $store = $this->initMockDataStore(false);
        $store->expects($this->once())
                  ->method('delete')
                  ->with($this->equalTo('mix'))
                  ->will($this->returnValue($store));

        $this->assertEquals($this->tracker, $this->tracker->unregister('mix'));
    }

    /**
     * getProperty() should call get() on the data storage layer
     *
     * @covers Mixpanel\Tracker::getProperty
     */
    public function testGetPropertyCallsDataStorageGet() {
        $store = $this->initMockDataStore(false);
        $store->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('mix'))
                  ->will($this->returnValue(false));

        $this->assertFalse($this->tracker->getProperty('mix'));
    }

    /**
     * updateReferrerInfo() should end up calling add() on the data storage twice
     *
     * @covers Mixpanel\Tracker::updateReferrerInfo
     * @covers Mixpanel\Tracker::registerOnce
     */
    public function testUpdateReferrerInfoShouldCallDataStorageAddTwice() {
        $store = $this->initMockDataStore(false);
        $store->expects($this->exactly(2))
                  ->method('add')
                  ->will($this->returnValue($store));

        $method = new ReflectionMethod('Mixpanel\Tracker', 'updateReferrerInfo');
        $method->setAccessible(true);

        $this->assertSame($this->tracker, $method->invoke($this->tracker));
    }

    /**
     * updateGoogleCampaignInfo() should end up calling add() on the data storage
     * as many times as we have campaign params
     *
     * @covers Mixpanel\Tracker::updateGoogleCampaignInfo
     * @covers Mixpanel\Tracker::registerOnce
     */
    public function testUpdateGoogleCampaignInfoShouldCallDataStorageAdd() {
        $store = $this->initMockDataStore(false);

        $_GET = array(
            'utm_source'   => '1',
            'utm_medium'   => '2',
            'utm_campaign' => '3',
            'utm_content'  => '4',
            'utm_term'     => '5',

            'shouldNot'    => 'beIncluded',
        );

        $store->expects($this->exactly(5))
              ->method('add')
              ->will($this->returnValue($store));

        $method = new ReflectionMethod('Mixpanel\Tracker', 'updateGoogleCampaignInfo');
        $method->setAccessible(true);

        $this->assertSame($this->tracker, $method->invoke($this->tracker));

        $_GET = array();
    }

    /**
     * updateSearchKeyword() should end up calling add() on the data storage
     * as many times as we have campaign params
     *
     * @covers Mixpanel\Tracker::updateSearchKeyword
     * @covers Mixpanel\Tracker::getReferrer
     * @covers Mixpanel\Tracker::getSearchEngineFromUrl
     * @covers Mixpanel\Tracker::register
     */
    public function testUpdateSearchKeyword() {

        $method = new ReflectionMethod('Mixpanel\Tracker', 'updateSearchKeyword');
        $method->setAccessible(true);

        $store = $this->initMockDataStore(false);
        // URLs with q/p param will give two params per URL (query + engine),
        // new google URL will still add engine, but not query
        $store->expects($this->exactly((4 * 2) + 1))
              ->method('set')
              ->will($this->returnValue($store));

        $referers = array(
            'http://www.kvasir.no/alle?q=mixpanel-php',
            'https://www.google.com/search?channel=fs&q=mixpanel-php&ie=utf-8&oe=utf-8',
            'http://no.search.yahoo.com/search;_ylt=whoa_;_ylc=thatsalonguglyurl--?p=mixpanel-php&toggle=1&cop=mss&ei=UTF-8&fr=yfp-t-734',
            'http://www.bing.com/search?q=mixpanel-php&go=&qs=n&form=QBLH&filt=all&pq=mixpanel-php&sc=0-6&sp=-1&sk=',
            'http://www.google.no/#hl=en&sclient=psy-ab&q=mixpanel-php', // New Google URL
            'https://duckduckgo.com/?q=mixpanel-php',
        );

        foreach ($referers as $referer) {
            $_SERVER['HTTP_REFERER'] = $referer;

            $this->assertSame($this->tracker, $method->invoke($this->tracker));
        }

        unset($_SERVER['HTTP_REFERER']);
    }

    private function initMockUuidGenerator() {
        $uuidGenerator = $this->getMock('Mixpanel\Uuid\GeneratorInterface');
        $uuidGenerator->expects($this->once())
                      ->method('generate')
                      ->will($this->returnValue('uuid'));

        $this->tracker->setUuidGenerator($uuidGenerator);

        return $uuidGenerator;
    }

    private function initMockRequester() {
        $requester = $this->getMock('Mixpanel\Request\RequestInterface');
        $requester->expects($this->once())
                  ->method('request')
                  ->with($this->matchesRegularExpression('#^http://api.mixpanel.com/track#'))
                  ->will($this->returnValue(true));

        $this->tracker->setRequestMethod($requester);

        return $requester;
    }

    private function initMockDataStore($expectGetState = true) {
        $store = $this->getMock('Mixpanel\DataStorage\StorageInterface');
        $this->tracker->setDataStorage($store);

        if ($expectGetState) {
            $dataState = array(
                '$initial_referrer'         => '$direct',
                '$initial_referring_domain' => '$direct',
            );

            $store->expects($this->any())
                  ->method('getState')
                  ->will($this->returnValue($dataState));
        }

        return $store;
    }

}
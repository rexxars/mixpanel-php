<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mixpanel\DataStorage;

use ReflectionMethod;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class CookieTest extends \PHPUnit_Framework_TestCase {
    /**
     * Cookie instance
     *
     * @var Cookie
     */
    protected $cookie;

    /**
     * Cookie name
     *
     * @var string
     */
    protected $cookieName = 'mixpanel_210aa494a3c055025a2e7b0dc6112009_mp';

    /**
     * Project token
     *
     * @var string
     */
    protected $projectToken = '210aa494a3c055025a2e7b0dc6112009';

    /**
     * Set up the cookie
     *
     */
    public function setUp() {
        $this->cookie = new Cookie();
        $this->cookie->setProjectToken($this->projectToken);
    }

    /**
     * Tear down the cookie
     */
    public function tearDown() {
        $this->cookie = null;

        unset($_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME']);
        unset($_COOKIE[$this->cookieName]);
    }

    /**
     * Test that useCrossSubdomainCookie() actually sets the value passed
     *
     * @covers Mixpanel\DataStorage\Cookie::useCrossSubdomainCookie
     */
    public function testUseCrossSubdomainCookieStoresValue() {
        $this->cookie->useCrossSubdomainCookie(false);
        $this->assertFalse($this->cookie->useCrossSubdomainCookie());

        $this->cookie->useCrossSubdomainCookie(true);
        $this->assertTrue($this->cookie->useCrossSubdomainCookie());
    }

    /**
     * Test that useCrossSubdomainCookie() defaults to true
     *
     * @covers Mixpanel\DataStorage\Cookie::useCrossSubdomainCookie
     */
    public function testUseCrossSubdomainCookieDefaultsTrue() {
        $this->assertTrue($this->cookie->useCrossSubdomainCookie());
    }

    /**
     * Test that we can set and get the cookie domain
     *
     * @covers Mixpanel\DataStorage\Cookie::setCookieDomain
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     */
    public function testSetAndGetCookieDomain() {
        $this->cookie->setCookieDomain('.tech.vg.no');
        $this->assertSame('.tech.vg.no', $this->cookie->getCookieDomain());

        $this->cookie->setCookieDomain('.mixpanel.com');
        $this->assertSame('.mixpanel.com', $this->cookie->getCookieDomain());
    }

    /**
     * Test that getCookieDomain returns null if we
     * specify not to use cross subdomain cookies
     *
     * @covers Mixpanel\DataStorage\Cookie::useCrossSubdomainCookie
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     */
    public function testGetCookieDomainReturnsNullIfLocalCookiesAreUsed() {
        $this->cookie->useCrossSubdomainCookie(false);
        $this->assertNull($this->cookie->getCookieDomain());
    }

    /**
     * Test that we can get the correct cookie domain from HTTP_HOST
     *
     * @covers Mixpanel\DataStorage\Cookie::useCrossSubdomainCookie
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     */
    public function testGetCookieDomainReturnsCorrectValueFromHttpHost() {
        $this->cookie->useCrossSubdomainCookie(true);

        // NOTE: This is actually wrong, and should return .vg.no
        //       However, this is what the JS SDK from Mixpanel returns -
        //       as such, we'll return the same to prevent writing duplicates
        $_SERVER['HTTP_HOST'] = 'tech.vg.no';
        $this->assertSame('.tech.vg.no', $this->cookie->getCookieDomain());

        // NOTE: Also incorrect
        $_SERVER['HTTP_HOST'] = 'www.vg.no';
        $this->assertSame('.www.vg.no', $this->cookie->getCookieDomain());

        $_SERVER['HTTP_HOST'] = 'vg.no';
        $this->assertSame('.vg.no', $this->cookie->getCookieDomain());

        $_SERVER['HTTP_HOST'] = 'www.mixpanel.com';
        $this->assertSame('.mixpanel.com', $this->cookie->getCookieDomain());
    }

    /**
     * Test that we can get the correct cookie domain from SERVER_NAME
     *
     * @covers Mixpanel\DataStorage\Cookie::useCrossSubdomainCookie
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     */
    public function testGetCookieDomainReturnsCorrectValueFromServerName() {
        $this->cookie->useCrossSubdomainCookie(true);

        // NOTE: This is actually wrong, and should return .vg.no
        //       However, this is what the JS SDK from Mixpanel returns -
        //       as such, we'll return the same to prevent writing duplicates
        $_SERVER['SERVER_NAME'] = 'tech.vg.no';
        $this->assertSame('.tech.vg.no', $this->cookie->getCookieDomain());

        // NOTE: Also incorrect
        $_SERVER['SERVER_NAME'] = 'www.vg.no';
        $this->assertSame('.www.vg.no', $this->cookie->getCookieDomain());

        $_SERVER['SERVER_NAME'] = 'vg.no';
        $this->assertSame('.vg.no', $this->cookie->getCookieDomain());

        $_SERVER['SERVER_NAME'] = 'www.mixpanel.com';
        $this->assertSame('.mixpanel.com', $this->cookie->getCookieDomain());
    }

    /**
     * Test that getCookieDomain() returns null if domain could not be determined
     *
     * @covers Mixpanel\DataStorage\Cookie::useCrossSubdomainCookie
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     */
    public function testGetCookieDomainRetrievesCorrectValueFromServerVars() {
        $this->cookie->useCrossSubdomainCookie(true);

        $this->assertNull($this->cookie->getCookieDomain());
    }

    /**
     * Test that getCookiePath() defaults to '/'
     *
     * @covers Mixpanel\DataStorage\Cookie::getCookiePath
     */
    public function testGetCookiePathDefaultsToRoot() {
        $this->assertSame('/', $this->cookie->getCookiePath());
    }

    /**
     * Test that we can get and set cookie path
     *
     * @covers Mixpanel\DataStorage\Cookie::setCookiePath
     * @covers Mixpanel\DataStorage\Cookie::getCookiePath
     */
    public function testSetAndGetCookiePath() {
        $path = '/some/path';

        $this->assertEquals($this->cookie, $this->cookie->setCookiePath($path));
        $this->assertSame($path, $this->cookie->getCookiePath());
    }

    /**
     * Test that we can get and set the storage key
     *
     * @covers Mixpanel\DataStorage\Cookie::setStorageKey
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     */
    public function testGetAndSetStorageKey() {
        $key = 'some_key';
        $this->assertEquals($this->cookie, $this->cookie->setStorageKey($key));
        $this->assertSame($key, $this->cookie->getStorageKey());
    }

    /**
     * Test that generateStorageKey creates the correct cookie name
     *
     * @covers Mixpanel\DataStorage\Cookie::setProjectToken
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::generateStorageKey
     */
    public function testGenerateStorageKeyReturnsCorrectCookieName() {
        $this->assertEquals($this->cookie, $this->cookie->setProjectToken('project-token'));
        $this->assertSame('mixpanel_project-token_mp', $this->cookie->getStorageKey());
    }

    /**
     * Test that we can get and set the session lifetime
     *
     * @covers Mixpanel\DataStorage\Cookie::setLifetime
     * @covers Mixpanel\DataStorage\Cookie::getLifetime
     */
    public function testGetAndSetLifetime() {
        $lifetime = 3600;
        $this->assertEquals($this->cookie, $this->cookie->setLifetime($lifetime));
        $this->assertSame($lifetime, $this->cookie->getLifetime());
    }

    /**
     * Test that getState returns empty array if no cookie is set
     *
     * @covers Mixpanel\DataStorage\Cookie::getState
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::generateStorageKey
     * @covers Mixpanel\DataStorage\Cookie::filterState
     */
    public function testGetStateReturnsEmptyArrayIfNoCookieSet() {
        $this->assertEmpty($this->cookie->getState());
    }

    /**
     * Test that getState returns empty array on invalid cookie
     *
     * @covers Mixpanel\DataStorage\Cookie::getState
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::generateStorageKey
     * @covers Mixpanel\DataStorage\Cookie::filterState
     */
    public function testGetStateReturnsEmptyArrayIfCookieInvalid() {
        $_COOKIE[$this->cookieName] = '{"invalid:cookie}';
        $this->assertEmpty($this->cookie->getState());
    }

    /**
     * Test that getState returns correct array
     *
     * @covers Mixpanel\DataStorage\Cookie::getState
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::generateStorageKey
     * @covers Mixpanel\DataStorage\Cookie::filterState
     */
    public function testGetStateReturnsCorrectArrayFromCookie() {
        $data = array(
            'foo'         => 'bar',
            'distinct_id' => 'some-uuid',
        );

        $_COOKIE[$this->cookieName] = json_encode($data);
        $this->assertSame($data, $this->cookie->getState());
    }

    /**
     * Test that getState does not return double-underscore-prefixed keys
     *
     * @covers Mixpanel\DataStorage\Cookie::getState
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::generateStorageKey
     * @covers Mixpanel\DataStorage\Cookie::filterState
     */
    public function testGetStateReturnsFilteredArrayFromCookie() {
        $data = array(
            'foo'         => 'bar',
            '__alias'     => 'somealias',
            '__mtp'       => 'sure',
            'distinct_id' => 'some-uuid',
        );

        $_COOKIE[$this->cookieName] = json_encode($data);
        $this->assertSame(array(
            'foo'         => 'bar',
            'distinct_id' => 'some-uuid'
        ), $this->cookie->getState());
    }

    /**
     * Test that storeState returns the correct Set-Cookie header with
     * custom properties set
     *
     * @runInSeparateProcess
     * @covers Mixpanel\DataStorage\Cookie::storeState
     * @covers Mixpanel\DataStorage\Cookie::setHeader
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::getLifetime
     * @covers Mixpanel\DataStorage\Cookie::getCookiePath
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     * @covers Mixpanel\DataStorage\Cookie::getHeaderList
     */
    public function testStoreStateReturnsCorrectSetCookieHeaderWithCustomProperties() {
        $this->cookie->setLifetime(2592000);
        $this->cookie->setCookiePath('/some/path');
        $this->cookie->setCookieDomain('.vg.no');

        $this->cookie->storeState();

        $method = new ReflectionMethod('Mixpanel\DataStorage\Cookie', 'getHeaderList');
        $method->setAccessible(true);

        $headers = $method->invoke($this->cookie);
        $numCookies = 0;

        $regex  = '#^Set-Cookie: ' . preg_quote($this->cookieName) . '=[^;]+; ';
        $regex .= 'expires=\w+, \d\d-\w+-\d{4} \d\d:\d\d:\d\d \w+; ';
        $regex .= 'path=/some/path; domain=\.vg\.no$#';

        $matchFound = false;
        foreach ($headers as $header) {
            if (preg_match($regex, $header)) {
                $matchFound = true;
            }
        }

        $this->assertTrue($matchFound);
    }

    /**
     * Test that storeState returns the cookie after storing the data
     *
     * @runInSeparateProcess
     * @covers Mixpanel\DataStorage\Cookie::storeState
     * @covers Mixpanel\DataStorage\Cookie::setHeader
     * @covers Mixpanel\DataStorage\Cookie::getState
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::getLifetime
     * @covers Mixpanel\DataStorage\Cookie::getCookiePath
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     * @covers Mixpanel\DataStorage\Cookie::getHeaderList
     * @covers Mixpanel\DataStorage\Cookie::removeHeader
     * @covers Mixpanel\DataStorage\Cookie::removeCookie
     */
    public function testStoreStateReturnsCookieAndSetsHeader() {
        $this->cookie->set('key', 'value');
        $this->assertEquals($this->cookie, $this->cookie->storeState());

        $method = new ReflectionMethod('Mixpanel\DataStorage\Cookie', 'getHeaderList');
        $method->setAccessible(true);

        $headers = $method->invoke($this->cookie);
        $numCookies = 0;
        foreach ($headers as $header) {
            if (preg_match('#^Set-Cookie:\s*' . preg_quote($this->cookieName) . '=#', $header)) {
                $numCookies++;
            }
        }

        $this->assertSame(1, $numCookies);
    }

    /**
     * Test that removeCookie does not remove non-mp cookies
     *
     * @runInSeparateProcess
     * @covers Mixpanel\DataStorage\Cookie::storeState
     * @covers Mixpanel\DataStorage\Cookie::setHeader
     * @covers Mixpanel\DataStorage\Cookie::getState
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::getLifetime
     * @covers Mixpanel\DataStorage\Cookie::getCookiePath
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     * @covers Mixpanel\DataStorage\Cookie::getHeaderList
     * @covers Mixpanel\DataStorage\Cookie::removeHeader
     * @covers Mixpanel\DataStorage\Cookie::removeCookie
     */
    public function testRemoveCookieDoesNotRemoveNonMpCookies() {
        $setHeader = new ReflectionMethod('Mixpanel\DataStorage\Cookie', 'setHeader');
        $setHeader->setAccessible(true);
        $setHeader->invoke($this->cookie, 'Set-Cookie: some=cookie');

        $this->cookie->set('key', 'value');
        $this->assertEquals($this->cookie, $this->cookie->storeState());
        $this->cookie->set('foo', 'bar');
        $this->cookie->set('mix', 'panel');
        $this->assertEquals($this->cookie, $this->cookie->storeState());

        $getHeaderList = new ReflectionMethod('Mixpanel\DataStorage\Cookie', 'getHeaderList');
        $getHeaderList->setAccessible(true);
        $headers      = $getHeaderList->invoke($this->cookie);

        $mpCookies    = 0;
        $nonMpCookies = 0;
        foreach ($headers as $header) {
            if (preg_match('#^Set-Cookie:\s*' . preg_quote($this->cookieName) . '=#', $header)) {
                $mpCookies++;
            } else if (preg_match('#^Set-Cookie:#', $header)) {
                $nonMpCookies++;
            }
        }

        $this->assertSame(1, $mpCookies);
        $this->assertSame(1, $nonMpCookies);
    }

    /**
     * Test that removeCookie leaves non-cookie headers
     *
     * @runInSeparateProcess
     * @covers Mixpanel\DataStorage\Cookie::storeState
     * @covers Mixpanel\DataStorage\Cookie::setHeader
     * @covers Mixpanel\DataStorage\Cookie::getState
     * @covers Mixpanel\DataStorage\Cookie::getStorageKey
     * @covers Mixpanel\DataStorage\Cookie::getLifetime
     * @covers Mixpanel\DataStorage\Cookie::getCookiePath
     * @covers Mixpanel\DataStorage\Cookie::getCookieDomain
     * @covers Mixpanel\DataStorage\Cookie::getHeaderList
     * @covers Mixpanel\DataStorage\Cookie::removeHeader
     * @covers Mixpanel\DataStorage\Cookie::removeCookie
     */
    public function testRemoveCookieLeavesNonCookieHeaders() {
        $aweHeader = 'X-The-Sum-Of-Awe: http://tech.vg.no/';

        $setHeader = new ReflectionMethod('Mixpanel\DataStorage\Cookie', 'setHeader');
        $setHeader->setAccessible(true);
        $setHeader->invoke($this->cookie, $aweHeader);

        $this->cookie->set('mix', 'panel');
        $this->cookie->set('foo', 'bar');

        $getHeaderList = new ReflectionMethod('Mixpanel\DataStorage\Cookie', 'getHeaderList');
        $getHeaderList->setAccessible(true);
        $headers      = $getHeaderList->invoke($this->cookie);

        $mpCookies = 0;
        $sumOfAwe  = 0;
        foreach ($headers as $header) {
            if (preg_match('#^Set-Cookie:\s*' . preg_quote($this->cookieName) . '=#', $header)) {
                $mpCookies++;
            } else if ($header == $aweHeader) {
                $sumOfAwe++;
            }
        }

        $this->assertSame(1, $mpCookies);
        $this->assertSame(1, $sumOfAwe);
    }

}
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

use DateTime;

/**
 * Cookie data storage
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class Cookie extends StorageAbstract implements StorageInterface {

    /**
     * Whether to use cross-subdomain cookie
     *
     * @var boolean
     */
    protected $crossSubdomain = true;

    /**
     * Cookie domain
     *
     * @var string
     */
    protected $cookieDomain = null;

    /**
     * Cookie path
     *
     * @var string
     */
    protected $cookiePath = '/';

    /**
     * List of headers to be sent
     *
     * @var array
     */
    protected $headerList = array();

    /**
     * Sets or gets whether to use cross-subdomain cookie
     *
     * @param  boolean $mod
     * @return Cookie
     */
    public function useCrossSubdomainCookie($mod = null) {
        if (is_null($mod)) {
            return $this->crossSubdomain;
        }

        $this->crossSubdomain = (bool) $mod;
        return $this;
    }

    /**
     * Set domain to set cookies for
     *
     * @param  string $domain Domain name to set
     * @return Cookie
     */
    public function setCookieDomain($domain) {
        $this->cookieDomain = $domain;

        return $this;
    }

    /**
     * Get the domain to set cookie for
     *
     * NOTE: This yields the *same* results as the JS SDK, but unfortunately
     * this gives "incorrect" top-domain results. To play nice with the JS SDK,
     * we choose to follow their way of doing it.
     *
     * (tech.vg.no should return .vg.no, actually returns .tech.vg.no)
     *
     * @return string|null
     */
    public function getCookieDomain() {
        if (!is_null($this->cookieDomain)) {
            return $this->cookieDomain;
        }

        if (!$this->crossSubdomain) {
            // Set current domain if we don't use cross-subdomain cookies
            return null;
        }

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;
        $host = !$host && isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $host;

        if (preg_match('/[a-z0-9][a-z0-9\-]+\.[a-z\.]{2,6}$/i', $host, $matches)) {
            return '.' . $matches[0];
        }

        return null;
    }

    /**
     * Get path the cookie is valid for
     *
     * @return string
     */
    public function getCookiePath() {
        return $this->cookiePath;
    }

    /**
     * Set path the cookie should be valid for
     *
     * @param  string $path Path to set
     * @return Cookie
     */
    public function setCookiePath($path) {
        $this->cookiePath = $path;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getState() {
        if (is_null($this->state)) {
            $key = $this->getStorageKey();
            $this->state = array();

            if (isset($_COOKIE[$key])) {
                $this->state = json_decode($_COOKIE[$key], true) ?: array();
            }

        }

        return $this->filterState($this->state);
    }

    /**
     * {@inheritdoc}
     */
    public function storeState() {
        $cookieName = $this->getStorageKey();

        // Remove old Mixpanel-cookies before sending the new one
        $this->removeCookie($cookieName);

        // We're not using setcookie because we want to be able to unittest
        // this in a more elegant fashion
        $expire = gmdate('D, d-M-Y H:i:s \G\M\T', time() + $this->getLifetime());
        $domain = $this->getCookieDomain();

        $header  = 'Set-Cookie: ' . $cookieName . '=';
        $header .= rawurlencode(json_encode($this->getState()));
        $header .= '; expires=' . $expire;
        $header .= '; path=' . $this->getCookiePath();

        if (!is_null($domain)) {
            $header .= '; domain=' . $domain;
        }

        $this->setHeader($header);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateStorageKey() {
        return 'mp_' . $this->projectToken . '_mixpanel';
    }

    /**
     * Returns a list of the HTTP headers ready to be sent
     *
     * @return array
     */
    protected function getHeaderList() {
        // If we're in unit-testing mode, return internal header list
        return defined('MIXPANEL_TESTING') ? $this->headerList : headers_list();
    }

    /**
     * Queue a header for sending
     *
     * @param  string $header
     * @return Cookie
     */
    protected function setHeader($header) {
        $this->headerList[] = $header;

        header($header, false);

        return $this;
    }

    /**
     * Remove headers ready to be sent with the given name
     *
     * @param  string $headerName Name of the header to remove
     * @return Cookie
     */
    protected function removeHeader($headerName) {
        $this->headerList = array_filter($this->headerList, function($header) use ($headerName) {
            return !preg_match('#^' . preg_quote($headerName) . ':#', $header);
        });

        header_remove($headerName);

        return $this;
    }

    /**
     * Note: Ugly hack here to prevent < PHP 5.4 sending multiple Set-Cookie
     * headers. For PHP 5.4 and up we'd use header_register_callback.
     *
     * Alternatively, we could force the user to explicitly call storeState,
     * but for simplicity I'd rather hack around it.
     *
     * What this method does is the following:
     *  - Loop through all the headers ready to be sent
     *  - Fetch all Set-Cookie headers
     *  - Check if the cookie name is the one we want to remove
     *    - If no, add the cookie to an array
     *  - Remove all Set-Cookie headers queued for sending
     *  - Loop through all the temporarily stored cookies queued for sending
     *  - Re-add them to the headers list
     *  - Our cookie is now removed from header list and ready to be re-added
     *
     * @param  string $cookieName Name of cookie to remove
     * @return Cookie
     */
    protected function removeCookie($cookieName) {
        $nonMpCookies = array();
        $headers      = $this->getHeaderList();

        foreach ($headers as $header) {
            $parts = explode(': ', $header, 2);

            if (strtolower($parts[0]) != 'set-cookie') {
                // Not a Set-Cookie header, leave it alone
                continue;
            }

            if (strpos($parts[1], $cookieName . '=') !== 0) {
                // This is not the cookie we want to remove
                // Add it to our list of cookies to re-send
                $nonMpCookies[] = $header;
            }
        }

        // Remove all Set-Cookie headers
        $this->removeHeader('Set-Cookie');

        // Re-send all non-mixpanel cookies
        foreach ($nonMpCookies as $cookie) {
            $this->setHeader($cookie);
        }

        return $this;
    }

}
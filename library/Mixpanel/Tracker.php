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

use Mixpanel\Request\RequestInterface;

/**
 * PHP Mixpanel tracker
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class Tracker {

    /**
     * Token used to identify the project
     *
     * @var string
     */
    protected $token;

    /**
     * Distinct ID of the user
     *
     * @var string
     */
    protected $distinctId;

    /**
     * Whether to trust proxies x-forwarded-for header
     *
     * @var boolean
     */
    protected $trustProxy = false;

    /**
     * Mixpanel API host
     *
     * @var string
     */
    protected $apiHost = 'api.mixpanel.com';

    /**
     * The request method to use, based on system/setup
     *
     * @var RequestInterface
     */
    protected $requestMethod;

    /**
     * The clients user agent
     *
     * @var string
     */
    protected $userAgent;

    /**
     * CookieWriter instance
     *
     * @var CookieWriter
     */
    protected $cookieWriter;

    /**
     * UuidGenerator instance
     *
     * @var Uuid\GeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * The IP of the client
     *
     * @var string
     */
    protected $clientIp;

    /**
     * The preferred request method order
     *
     * @var array
     */
    protected $requestMethodOrder = array(
        'Mixpanel\Request\CliCurl',
        'Mixpanel\Request\Curl',
        'Mixpanel\Request\Socket',
    );

    /**
     * Constructs a new Mixpanel tracker with a given project token
     *
     * @param string $token Token for the project
     * @return Tracker
     */
    public function __construct($token = null) {
        if (!is_null($token)) {
            $this->setToken($token);
        }
    }

    /**
     * Sets the token to identify which project the events belong to
     *
     * @param string $token Token for the project
     * @return Tracker
     */
    public function setToken($token) {
        $this->token = $token;

        return $this;
    }

    /**
     * Returns the project token
     *
     * @return string
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Sets the request method to use
     *
     * @param RequestInterface Request method to use
     * @return RequestInterface
     */
    public function setRequestMethod(RequestInterface $method) {
        $this->requestMethod = $method;

        return $method;
    }

    /**
     * Set the order of request methods to try
     *
     * @param array $order
     * @return Tracker
     */
    public function setRequestMethodOrder(array $order) {
        $this->requestMethodOrder = $order;

        return $this;
    }

    /**
     * Determine the best possible request method for this system/setup
     *
     * @return RequestInterface
     */
    public function getRequestMethod() {
        if (!is_null($this->requestMethod)) {
            return $this->requestMethod;
        }

        foreach ($this->requestMethodOrder as $method) {
            $method = is_string($method) ? new $method() : $method;

            if ($method->isSupported()) {
                return $this->setRequestMethod($method);
            }
        }

        return false;
    }

    /**
     * Set the clients user agent
     *
     * @param string $ua User agent string to set
     * @return Tracker
     */
    public function setClientUserAgent($ua) {
        $this->userAgent = $ua;

        return $this;
    }

    /**
     * Returns the clients user agent, or a PHP version if not set (CLI-scripts etc)
     *
     * @return string
     */
    public function getClientUserAgent() {
        if (!is_null($this->userAgent)) {
            return $this->userAgent;
        }

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;
        $ua = $ua ? $ua : ('PHP ' . phpversion());

        $this->setClientUserAgent($ua);

        return $ua;
    }

    /**
     * Gets an instance of the UUID generator
     *
     * @return Uuid\GeneratorInterface
     */
    public function getUuidGenerator() {
        if (!is_null($this->uuidGenerator)) {
            return $this->uuidGenerator;
        }

        $this->setUuidGenerator(new Uuid\Generator());
        return $this->uuidGenerator;
    }

    /**
     * Set UUID generator instance
     *
     * @param Uuid\GeneratorInterface $generator
     * @return Tracker
     */
    public function setUuidGenerator(Uuid\GeneratorInterface $generator) {
        $this->uuidGenerator = $generator;

        return $this;
    }

    /**
     * Set client IP address
     *
     * @param string $ip
     * @return Tracker
     */
    public function setClientIp($ip) {
        $this->clientIp = $ip;

        return $this;
    }

    /**
     * Returns the clients IP-address
     *
     * @return string
     */
    public function getClientIp() {
        if (!is_null($this->clientIp)) {
            return $this->clientIp;
        }

        if ($this->trustProxy && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clients = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return empty($clients[0]) ? $_SERVER['REMOTE_ADDR'] : $clients[0];
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }

    /**
     * Set a distinct ID for the current user
     *
     * @param string $distinctId Distinct ID of the user
     * @return Tracker
     */
    public function identify($distinctId) {
        $this->distinctId = $distinctId;

        return $this;
    }

    /**
     * Get the distinct ID for the current user
     *
     * @return string|boolean Returns false if no distinct ID has been set
     */
    public function getDistinctId() {
        if (!is_null($this->distinctId)) {
            return $this->distinctId;
        }

        $cookie = $this->getCookieProperties();
        return isset($cookie['distinct_id']) ? $cookie['distinct_id'] : false;
    }

    /**
     * Whether to trust proxies x-forwarded-for header
     *
     * @param  boolean $mod True if we should trust the IP sent by HTTP-headers
     * @return Tracker
     */
    public function trustProxy($mod = null) {
        if (is_null($mod)) {
            return $this->trustProxy;
        }

        $this->trustProxy = (bool) $mod;

        return $this;
    }

    /**
     * Give the current user an alias (such as the unique ID from a database)
     * After aliasing, you should take care to always call identify with the
     * alias instead of relying on the old auto-generated distinct ID
     *
     * @param string $alias
     * @return boolean
     */
    public function alias($alias) {
        // Don't try to create alias when we have nothing to alias it to
        if ($this->getDistinctId() == false) {
            return false;
        }

        return $this->track('$create_alias', array(
            'alias' => $alias,
        ));
    }

    /**
     * Tracks an event with an optional set of properties
     *
     * @param string $event Event name to track
     * @param array $properties Optional properties to track for this event
     * @return boolean
     */
    public function track($event, $properties = array()) {
        // Merge cookie properties, defaults and explicitly passed
        $params = array(
            'event'      => $event,
            'properties' => array_merge(
                $this->getCookieProperties(),
                $this->getDefaultProperties(),
                $properties
            ),
        );

        // Make sure we include the project token
        if (!isset($params['properties']['token'])) {
            if ($this->token) {
                $params['properties']['token'] = $this->token;
            } else {
                // No token? Don't track event
                return false;
            }
        }

        // If no IP has been explicitly set, fetch it
        if (!isset($params['properties']['ip'])) {
            $ip = $this->getClientIp();
            if (!empty($ip) && $ip != '127.0.0.1') {
                $params['properties']['ip'] = $ip;
            }
        }

        // If user is identified, send it
        if (!is_null($this->distinctId)) {
            $params['properties']['distinct_id'] = $this->distinctId;
        }

        // No distinct ID set, and no cookie found? Create UUID and save it
        if (!isset($params['properties']['distinct_id'])) {
            $ua = $this->getClientUserAgent();
            $ip = $this->getClientIp();

            $uuid = $this->getUuidGenerator()->generate($ua, $ip);
            $this->identify($uuid);
            $params['properties']['distinct_id'] = $uuid;
            $this->storeUuid();
        }

        // Build URL and send tracking request in background
        $url = 'http://' . $this->apiHost . '/track/?data=' . base64_encode(json_encode($params)) . '&ip=1';

        // Perform request
        return $this->getRequestMethod()->request($url);
    }

    /**
     * Get the name of the cookie used to store mixpanel data
     *
     * @return string
     */
    protected function getCookieName() {
        return 'mp_' . $this->token . '_mixpanel';
    }

    /**
     * Get the domain to set cookie for
     *
     * @return string|null
     */
    protected function getCookieDomain() {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;
        $host = !$host && isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $host;

        if (preg_match('/[a-z0-9][a-z0-9\-]+\.[a-z\.]{2,6}$/i', $host, $matches)) {
            return '.' . $matches[0];
        }

        return null;
    }

    /**
     * Store UUID in cookie (if no UUID is already set)
     *
     * @return boolean
     */
    protected function storeUuid() {
        $writer = $this->getCookieWriter();

        $cookieName = $this->getCookieName();
        if (isset($_COOKIE[$cookieName]) || $writer->canSend() == false) {
            return false;
        }

        return $writer->setCookie(
            $cookieName,
            json_encode(array('distinct_id' => $this->getDistinctId())),
            time() + (365 * 24 * 60 * 60),
            '/',
            $this->getCookieDomain()
        );
    }

    /**
     * Get the properties stored in cookie
     *
     * @return array
     */
    protected function getCookieProperties() {
        $cookieName = $this->getCookieName();
        $params = false;
        if (isset($_COOKIE[$cookieName])) {
            $params = json_decode($_COOKIE[$cookieName], true);

            // Alias should never be included
            unset($params['__alias']);
        }

        return $params ?: array();
    }

    /**
     * Returns a normalized version of the clients operating system
     *
     * @return string
     */
    protected function getClientOperatingSystem() {
        $ua = $this->getClientUserAgent();

        if (preg_match('/Windows/i', $ua)) {
            return preg_match('/Phone/', $ua) ? 'Windows Mobile' : 'Windows';
        } else if (preg_match('/(iPhone|iPad|iPod)/', $ua)) {
            return 'iOS';
        } else if (preg_match('/Android/', $ua)) {
            return 'Android';
        } else if (preg_match('/(BlackBerry|PlayBook|BB10)/i', $ua)) {
            return 'BlackBerry';
        } else if (preg_match('/Mac/i', $ua)) {
            return 'Mac OS X';
        } else if (preg_match('/Linux/', $ua)) {
            return 'Linux';
        }

        return '';
    }

    /**
     * Returns a normalized version of the clients browser name
     *
     * NOTE: This varies slightly from the JS-client as we do not have
     * access to a couple of the checks used on the client side, namely
     * window.opera and navigator.vendor
     *
     * The order of the checks are important since many user agents
     * include key words used in later checks.
     *
     * @return string
     */
    protected function getClientBrowser() {
        $ua = $this->getClientUserAgent();

        if (strpos($ua, 'Opera') !== false) {
            return strpos($ua, 'Mini') ? 'Opera Mini' : 'Opera';
        } else if (preg_match('/(BlackBerry|PlayBook|BB10)/i', $ua)) {
            return 'BlackBerry';
        } else if (strpos($ua, 'Chrome') !== false) {
            return 'Chrome';
        } else if (strpos($ua, 'Android') !== false) {
            return 'Android Mobile';
        } else if (strpos($ua, 'Apple') !== false && strpos($ua, 'Safari') !== false) {
            return strpos($ua, 'Mobile') !== false ? 'Mobile Safari' : 'Safari';
        } else if (strpos($ua, 'Konqueror') !== false) {
            return 'Konqueror';
        } else if (strpos($ua, 'Firefox') !== false) {
            return 'Firefox';
        } else if (strpos($ua, 'MSIE') !== false) {
            return 'Internet Explorer';
        } else if (strpos($ua, 'Gecko') !== false) {
            return 'Mozilla';
        }

        return '';
    }

    /**
     * Returns the default properties to send with tracking-request
     *
     * @return array
     */
    protected function getDefaultProperties() {
        return array(
            '$os'      => $this->getClientOperatingSystem(),
            '$browser' => $this->getClientBrowser(),
            'mp_lib'   => 'php',
        );
    }

    /**
     * Checks if the clients user agent is a blocked one
     *
     * @return boolean
     */
    protected function isBlockedUserAgent() {
        $ua = $this->getClientUserAgent();
        if (preg_match('/(google web preview|baiduspider|yandexbot)/i', $ua)) {
            return true;
        }

        return false;
    }

    /**
     * Gets an instance of the cookie writer
     *
     * @return CookieWriter
     */
    protected function getCookieWriter() {
        if (!is_null($this->cookieWriter)) {
            return $this->cookieWriter;
        }

        $this->setCookieWriter(new CookieWriter());
        return $this->cookieWriter;
    }

    /**
     * Set cookie writer instance
     *
     * @param CookieWriter $writer
     * @return Tracker
     */
    protected function setCookieWriter(CookieWriter $writer) {
        $this->cookieWriter = $writer;

        return $this;
    }

}
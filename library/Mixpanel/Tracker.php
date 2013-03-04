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
    protected $distinctId = null;

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
    protected $requestMethod = null;

    /**
     * The clients user agent
     *
     * @var string
     */
    protected $userAgent;

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
            $uuid = $this->generateUuid();
            $this->identify($uuid);
            $params['properties']['distinct_id'] = $uuid;
            $this->storeUuid();
        }

        // Build URL and send tracking request in background
        $url = 'http://' . $this->apiHost . '/track/?data=' . base64_encode(json_encode($params)) . '&ip=1';

        // Perform request
        return $this->request($url);
    }

    /**
     * Performs a request against the API using the best possible method
     *
     * @param  string $url
     * @return boolean
     */
    protected function request($url) {
        $method = $this->getRequestMethod();
        return $method->request($url);
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
        $cookieName = $this->getCookieName();
        if (isset($_COOKIE[$cookieName]) || headers_sent()) {
            return false;
        }

        setcookie(
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
     * Generates a UUID based on much of the same logic powering the JS client
     *
     * @return string
     */
    protected function generateUuid() {
        $ua = $this->getClientUserAgent();

        // Ticks entropy
        $ticksEntropy = function() {
            $date = round(microtime() * 1000);
            $i = 0;

            // This while loop figures how many ticks go by
            // before microtime returns a new number, ie the amount
            // of ticks that go by per millisecond
            while ($date == round(microtime() * 1000)) {
                $i++;
            }

            return base_convert($date, 10, 16) . base_convert($i, 10, 16);
        };

        // Random entropy
        $randomEntropy = function() {
            return base_convert(mt_rand(), 10, 16);
        };

        // User agent entropy
        // This function takes the user agent string, and then xors
        // together each sequence of 8 bytes.  This produces a final
        // sequence of 8 bytes which it returns as hex.
        $uaEntropy = function() use ($ua) {
            $buffer = array();
            $ret = 0;

            $xor = function($result, $byteArray) use (&$buffer) {
                $tmp = 0;
                for ($j = 0; $j < count($byteArray); $j++) {
                    $tmp = $tmp | ($buffer[$j] << $j * 8);
                }
                return $result ^ $tmp;
            };

            for ($i = 0; $i < strlen($ua); $i++) {
                $ch = ord($ua[$i]);
                array_unshift($buffer, $ch & 0xFF);
                if (count($buffer) >= 4) {
                    $ret = $xor($ret, $buffer);
                    $buffer = array();
                }
            }

            if (count($buffer) > 0) {
                $ret = $xor($ret, $buffer);
            }

            return base_convert($ret, 10, 16);
        };

        // IP-based entropy (replacement for screen resolution in JS client)
        $ipEntropy = function($ip) {
            $ip = preg_replace('/[:.]/', '', $ip);
            return base_convert(crc32($ip), 10, 16);
        };

        return implode('-', array(
            $ticksEntropy(),
            $randomEntropy(),
            $uaEntropy(),
            $ipEntropy($this->getClientIp()),
            $ticksEntropy()
        ));
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
     * Returns the clients IP-address
     *
     * @return string
     */
    protected function getClientIp() {
        if ($this->trustProxy && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clients = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return empty($clients[0]) ? $_SERVER['REMOTE_ADDR'] : $clients[0];
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : gethostname();
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

}
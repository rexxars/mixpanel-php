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

use Mixpanel\Request\RequestInterface,
    Mixpanel\DataStorage\StorageInterface;

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
     * DataStorage instance
     *
     * @var StorageInterface
     */
    protected $storage;

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
     * Configuration options
     *
     * @var array
     */
    protected $config = array(
        // Properties
        'storeGoogle'          => true,
        'saveReferrer'         => true,

        // Use test-queue?
        'test'                 => false,
    );

    /**
     * Constructs a new Mixpanel tracker with a given project token
     *
     * @param  string  $token  Token for the project
     * @param  array   $config Configuration options
     * @return Tracker
     */
    public function __construct($token = null, $config = null) {
        if (!is_null($token)) {
            $this->setToken($token);
        }

        if (is_array($config)) {
            $this->setConfig($config);
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
     * Set configuration
     *
     * @param  array   $config  See README.md for configuration options
     * @return Tracker
     */
    public function setConfig($config) {
        $this->config = array_merge($this->config, $config);

        return $this;
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

        $this->getDataStorage()->setUserUuid($distinctId);

        return $this;
    }

    /**
     * Get the distinct ID for the current user
     *
     * @return string|boolean Returns false if no distinct ID has been set
     */
    public function getDistinctId($createIfNotSet = true) {
        if (!is_null($this->distinctId)) {
            return $this->distinctId;
        }

        // See if we have a distinct ID for the user in data storage
        $distinctId = $this->getDataStorage()->get('distinct_id');
        if ($distinctId) {
            return $distinctId;
        }

        if (!$createIfNotSet) {
            return false;
        }

        // No user identified and no user is found in datastore
        // Generate a new UUID for the user
        $uuid = $this->getUuidGenerator()->generate(
            $this->getClientUserAgent(),
            $this->getClientIp()
        );

        $this->identify($uuid);

        return $uuid;
    }

    /**
     * Provide a string to recognize the user by. The string passed to this
     * method will appear in the Mixpanel Streams product rather than an
     * automatically generated name.  Name tags do not have to be unique.
     *
     * @param  string $name A human readable name for the user
     * @return Tracker
     */
    public function nameTag($name) {
        $this->getDataStorage()->set('mp_name_tag', $name);

        return $this;
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
     * NOTE: You should take care to only call alias ONCE per user!
     *
     * @param string $alias
     * @return boolean
     */
    public function alias($alias) {
        // Don't try to create alias when we have nothing to alias it to
        if ($this->getDistinctId(false) == false) {
            return false;
        }

        return $this->track('$create_alias', array(
            'alias' => $alias,
        ));
    }

    /**
     * Store a persistent set of properties for a user (i.e. super properties).
     * These properties are automatically included with all events sent by the user.
     *
     * @param  array  $properties A dictionary of information about the user to store.
     *                            This is often information you just learned, such as
     *                            the user's age or gender, that you'd like to send
     *                            with later events.
     * @return Tracker
     */
    public function register(array $properties) {
        $store = $this->getDataStorage();

        foreach ($properties as $key => $value) {
            $store->set($key, $value);
        }

        return $this;
    }

    /**
     * Store a persistent set of properties about a user (very similar to register()),
     * but only save them if they haven't been set before. Useful for storing one-time
     * values, or when you want first-touch attribution.
     *
     * @param  array   $properties A dictionary of information about the user to store.
     *                             This is often information you just learned, such as
     *                             the user's age or gender, that you'd like to send
     *                             with later events.
     * @param  string  $default    If the current value of the super property is this
     *                             default value (ex: "False", "None") and a different
     *                             value is set, we will override it. Defaults to "None".
     * @return Tracker
     */
    public function registerOnce(array $properties, $default = 'None') {
        $store = $this->getDataStorage();

        foreach ($properties as $key => $value) {
            $store->add($key, $value, $default);
        }

        return $this;
    }

    /**
     * Delete a super property stored on this user, if it exists.
     *
     * @param  string  $propertyName The name of the super property to remove.
     * @return Tracker
     */
    public function unregister($propertyName) {
        $this->getDataStorage()->delete($propertyName);

        return $this;
    }

    /**
     * Get the value of a super property by the property name.
     *
     * @param  string $propertyName The name of the super property to retrieve.
     * @return mixed
     */
    public function getProperty($propertyName) {
        return $this->getDataStorage()->get($propertyName);
    }

    /**
     * Trigger a pageview event for Streams. Events tracked this way will not be
     * available on Trends, Funnels or Retention. Only for use with Mixpanel Streams.
     *
     * @param  string $page The url of the page to record. If you don't include this,
     *                      it defaults to the current url.
     * @return boolean
     */
    public function trackPageView($page = null) {
        if (!preg_match('#^https?://#', $page)) {
            // Try to build a full URL
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
            $host  = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;

            $host  = $host ?: (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : false);
            $page  = $page ?: (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : false);

            $url   = ($https ? 'https' : 'http') . '://' . $host . $page;
        } else {
            $url   = $page;
        }

        return $this->track('mp_page_view', array(
            'mp_page' => $url
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
        // Check if the client has sent a blocked user agent
        if ($this->isBlockedUserAgent()) {
            return false;
        }

        if (!$this->token) {
            // No token? Don't track event
            return false;
        }

        // Grab search keyword from referrer
        $this->updateSearchKeyword();

        // Save google campaign info, if the option is not disabled
        if ($this->config['storeGoogle']) {
            $this->updateGoogleCampaignInfo();
        }

        // Save referrer, if the option is not disabled
        if ($this->config['saveReferrer']) {
            $this->updateReferrerInfo();
        }

        // Merge data storage state, defaults and explicitly passed
        $params = array(
            'event'      => $event,
            'properties' => array_merge(
                $this->getDataStorage()->getState(),
                $this->getDefaultProperties(),
                $properties
            ),
        );

        // Make sure we include the project token if not already set
        if (!isset($params['properties']['token'])) {
            $params['properties']['token'] = $this->token;
        }

        // If no IP has been explicitly set, fetch it
        if (!isset($params['properties']['ip'])) {
            $ip = $this->getClientIp();
            if (!empty($ip) && $ip != '127.0.0.1') {
                $params['properties']['ip'] = $ip;
            }
        }

        // Always let the explicitly set properties overwrite
        if (!isset($properties['distinct_id'])) {
            $params['properties']['distinct_id'] = $this->getDistinctId();
        }

        // Remove empty properties
        $params['properties'] = array_filter($params['properties']);

        // Build URL and send tracking request in background
        $getParams = array(
            'data' => base64_encode(json_encode($params)),
        );

        // Should we use test-queue?
        if ($this->config['test']) {
            $getParams['test'] = 1;
        }

        $url  = 'http://' . $this->apiHost . '/track/?';
        $url .= http_build_query($getParams);

        // Perform request
        return $this->getRequestMethod()->request($url);
    }

    /**
     * Gets an instance of a data storage adapter
     *
     * @return StorageInterface
     */
    public function getDataStorage() {
        if (!is_null($this->storage)) {
            return $this->storage;
        }

        // Use default data storage (cookie-based)
        $cookie = new DataStorage\Cookie();
        $cookie->setProjectToken($this->token);

        // Set data storage adapter
        $this->setDataStorage($cookie);

        // Only set user ID after data storage has been initialized
        $this->storage->setUserUuid($this->getDistinctId());

        return $this->storage;
    }

    /**
     * Set data storage adapter instance
     *
     * @param  StorageInterface $storage
     * @return Tracker
     */
    public function setDataStorage(StorageInterface $storage) {
        $this->storage = $storage;

        return $this;
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
     * Returns the clients device, if it can be determined
     *
     * @return string
     */
    protected function getClientDevice() {
        $ua = $this->getClientUserAgent();
        if (strpos($ua, 'iPhone') !== false) {
            return 'iPhone';
        } else if (strpos($ua, 'iPad') !== false) {
            return 'iPad';
        } else if (strpos($ua, 'iPod') !== false) {
            return 'iPod Touch';
        } else if (preg_match('/(BlackBerry|PlayBook|BB10)/i', $ua)) {
            return 'BlackBerry';
        } else if (stripos($ua, 'Windows Phone') !== false) {
            return 'Windows Phone';
        } else if (strpos($ua, 'Android') !== false) {
            return 'Android';
        }

        return '';
    }

    /**
     * Returns the default properties to send with tracking-request
     *
     * @return array
     */
    protected function getDefaultProperties() {
        $properties = array(
            '$os'               => $this->getClientOperatingSystem(),
            '$browser'          => $this->getClientBrowser(),
            '$referrer'         => $this->getReferrer(),
            '$referring_domain' => $this->getReferringDomain(),
            '$device'           => $this->getClientDevice(),
            'mp_lib'            => 'php',
        );

        return array_filter($properties);
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
     * Tries to extract search engine name from a URL
     *
     * @param  string $url The URL to analyze
     * @return string|null Returns engine name if matched, null otherwise
     */
    protected function getSearchEngineFromUrl($url) {
        if (empty($url)) {
            return null;
        } else if (preg_match('#^https?://(.*)google.([^/?]*)#', $url)) {
            return 'google';
        } else if (preg_match('#^https?://(.*)bing.com#', $url)) {
            return 'bing';
        } else if (preg_match('#https?://(.*)yahoo.([^/?]*)#', $url)) {
            return 'yahoo';
        } else if (preg_match('#https?://(.*)duckduckgo.com#', $url)) {
            return 'duckduckgo';
        }

        return null;
    }

    /**
     * Updates search keyword from referrer
     *
     * @return Tracker
     */
    protected function updateSearchKeyword() {
        $referrer  = $this->getReferrer();
        $engine    = $this->getSearchEngineFromUrl($referrer);

        if (empty($engine)) {
            return $this;
        }

        $params    = array('$search_engine' => $engine);
        $urlParts  = parse_url($referrer);
        $urlParams = isset($urlParts['query']) ? $urlParts['query'] : '';
        parse_str($urlParams, $queryParams);

        $param    = ($engine == 'yahoo') ? 'p' : 'q';

        if (!empty($queryParams[$param])) {
            $params['mp_keyword'] = $queryParams[$param];
        }

        return $this->register($params);
    }

    /**
     * Update Google campaign info
     *
     * @return Tracker
     */
    protected function updateGoogleCampaignInfo() {
        $keywords = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term');
        $params   = array_intersect_key($_GET, array_flip($keywords));

        return $this->registerOnce($params, '');
    }

    /**
     * Update initial referrer info
     *
     * @return Tracker
     */
    protected function updateReferrerInfo() {
        return $this->registerOnce(array(
            '$initial_referrer'         => $this->getReferrer()        ?: '$direct',
            '$initial_referring_domain' => $this->getReferringDomain() ?: '$direct',
        ), '');
    }

    /**
     * Get referrer for this request
     *
     * @return string
     */
    protected function getReferrer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
    }

    /**
     * Get referring domain for this request
     *
     * @return string
     */
    protected function getReferringDomain() {
        $url   = $this->getReferrer();

        if (empty($url)) {
            return '';
        }

        $parts = explode('/', $url);
        if (count($parts) >= 3) {
            return $parts[2];
        }

        return '';
    }

}
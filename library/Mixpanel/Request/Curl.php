<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mixpanel\Request;

/**
 * Curl request method
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class Curl implements RequestInterface {

    /**
     * Timeout for requests, in seconds
     *
     * @var integer
     */
    protected $timeout = 1;

    /**
     * Set timeout of requests, in seconds
     *
     * @param int $timeout Timeout of requests, in seconds
     * @return Curl
     */
    public function setTimeout($timeout) {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported() {
        return function_exists('curl_init');
    }

    /**
     * {@inheritdoc}
     */
    public function request($url, $returnResponse = false) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $returnResponse ? $response : true;
    }

}
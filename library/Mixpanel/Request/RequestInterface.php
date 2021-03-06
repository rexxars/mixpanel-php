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
 * Request interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
interface RequestInterface {

    /**
     * Checks if this request method is supported by the system
     *
     * @return boolean
     */
    function isSupported();

    /**
     * Request a resource
     *
     * @param string $url The URL to request
     * @param boolean $returnResponse Whether to return the response of the request or not (slower)
     * @return boolean
     */
    function request($url, $returnResponse = false);

}
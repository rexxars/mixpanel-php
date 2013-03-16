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
 * Socket request method
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class Socket implements RequestInterface {

    /**
     * {@inheritdoc}
     */
    public function isSupported() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function request($url, $returnResponse = false) {
        $parts = parse_url($url);

        if (!isset($parts['host']) || !isset($parts['path'])) {
            return false;
        }

        $fp = @fsockopen($parts['host'], $parts['port'], $errno, $errstr, 0.5);
        if (!$fp) {
            return false;
        }

        stream_set_timeout($fp, 0.5);

        $out  = 'GET ' . $parts['path'] . " HTTP/1.0\r\n";
        $out .= 'Host: ' . $parts['host'] . "\r\n";
        $out .= 'Connection: Close' . "\r\n\r\n";

        $in = '';
        fwrite($fp, $out);
        while (!feof($fp)) {
            $in .= fgets($fp, 128);
        }

        list($headers, $body) = explode("\r\n\r\n", $in, 2);

        fclose($fp);

        return $returnResponse ? $body : true;
    }

}
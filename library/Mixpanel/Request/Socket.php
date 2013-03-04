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
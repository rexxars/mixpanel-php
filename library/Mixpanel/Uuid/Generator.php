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

namespace Mixpanel\Uuid;

/**
 * Mixpanel UUID generator - based on partially same logic powering the JS client
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class Generator implements GeneratorInterface {

    /**
     * Generates a UUID based on much of the same logic powering the JS client
     *
     * @param  string $userAgent
     * @param  string $clientIp
     * @return string
     */
    public function generate($userAgent, $clientIp) {
        return implode('-', array(
            $this->ticksEntropy(),
            $this->randomEntropy(),
            $this->uaEntropy($userAgent),
            $this->ipEntropy($clientIp),
            $this->ticksEntropy()
        ));
    }

    /**
     * Generate entropy based on how many ticks go by in a millisecond
     *
     * @return string
     */
    protected function ticksEntropy() {
        $start = round(microtime() * 1000);
        $i = 0;

        // This while loop figures how many ticks go by
        // before microtime returns a new number, ie the amount
        // of ticks that go by per millisecond
        while ($start == round(microtime() * 1000)) {
            $i++;
        }

        return base_convert($start, 10, 16) . base_convert($i, 10, 16);
    }

    /**
     * Generate entropy based on user agent
     *
     * @param  string $userAgent
     * @return string
     */
    protected function uaEntropy($userAgent) {
        $buffer = array();
        $ret = 0;

        $xor = function($result, $byteArray) use (&$buffer) {
            $tmp = 0;
            for ($j = 0; $j < count($byteArray); $j++) {
                $tmp = $tmp | ($buffer[$j] << $j * 8);
            }
            return $result ^ $tmp;
        };

        for ($i = 0; $i < strlen($userAgent); $i++) {
            $ch = ord($userAgent[$i]);
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
    }

    /**
     * Generate some entropy based on mt_rand()
     *
     * @return string
     */
    protected function randomEntropy() {
        return base_convert(mt_rand(), 10, 16);
    }

    /**
     * IP-based entropy (replacement for screen resolution in JS client)
     *
     * @param  string $ip
     * @return string
     */
    protected function ipEntropy($ip) {
        $ip = preg_replace('/[:.]/', '', $ip);
        return base_convert(crc32($ip), 10, 16);
    }

}
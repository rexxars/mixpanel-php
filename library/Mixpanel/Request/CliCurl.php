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
 * Curl CLI request method
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class CliCurl implements RequestInterface {

    /**
     * {@inheritdoc}
     */
    public function isSupported() {
        exec('curl --version >/dev/null 2>&1', $out, $error);
        return $error <= 2;
    }

    /**
     * {@inheritdoc}
     */
    public function request($url, $returnResponse = false) {
        $add = $returnResponse ? '' : ' >/dev/null 2>&1 &';
        $cmd = 'curl --silent ' . escapeshellarg($url) . $add;

        exec($cmd, $out, $error);

        return $returnResponse ? join(PHP_EOL, $out) : !$error;
    }

}
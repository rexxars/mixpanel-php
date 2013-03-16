<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

header('Content-Type: application/json');
if (stripos($_SERVER['REQUEST_URI'], '/track') === 0) {
    echo '1';
} else {
    echo '0';
}
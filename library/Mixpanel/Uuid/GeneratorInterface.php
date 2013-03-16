<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mixpanel\Uuid;

/**
 * UUID generator interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
interface GeneratorInterface {

    /**
     * Generates a UUID to identify a user
     *
     * @param string $userAgent The clients user agent
     * @param string $clientIp  The clients IP address
     * @return string
     */
    public function generate($userAgent, $clientIp);

}
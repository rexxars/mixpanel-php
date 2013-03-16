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
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class CliCurlTest extends RequestTest {

    /**
     * Set up the request method
     *
     */
    public function setUp() {
        parent::setUp();

        $this->method = new CliCurl();
    }

}
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

use ReflectionProperty;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class CurlTest extends RequestTest {

    /**
     * Set up the request method
     *
     */
    public function setUp() {
        parent::setUp();

        $this->method = new Curl();
        $this->method->setTimeout(30);
    }

    /**
     * Curl method should be able to set a custom timeout
     *
     */
    public function testSetTimeoutSetsCorrectValue() {
        $property = new ReflectionProperty('Mixpanel\Request\Curl', 'timeout');
        $property->setAccessible(true);

        $this->method->setTimeout(5);
        $this->assertSame(5, $property->getValue($this->method));

        $this->method->setTimeout('7');
        $this->assertSame(7, $property->getValue($this->method));
    }

}
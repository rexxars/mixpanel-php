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
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase {
    /**
     * Generator instance
     *
     * @var Generator
     */
    protected $generator;

    /**
     * Set up the generator
     *
     */
    public function setUp() {
        $this->generator = new Generator();
    }

    /**
     * Tear down the generator
     */
    public function tearDown() {
        $this->generator = null;
    }

    /**
     * Generating UUID should return different results every time
     *
     * @covers Mixpanel\Uuid\Generator::generate
     * @covers Mixpanel\Uuid\Generator::ticksEntropy
     * @covers Mixpanel\Uuid\Generator::uaEntropy
     * @covers Mixpanel\Uuid\Generator::randomEntropy
     * @covers Mixpanel\Uuid\Generator::ipEntropy
     */
    public function testGeneratingUuidShouldReturnDifferentResultEveryTime() {
        $uuids = array();
        for ($i = 0; $i < 100; $i++) {
            $uuid = $this->generator->generate('user-agent', '127.0.0.1');
            $this->assertNotContains($uuid, $uuids);

            $uuids[] = $uuid;
        }
    }

}
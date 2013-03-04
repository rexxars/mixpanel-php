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
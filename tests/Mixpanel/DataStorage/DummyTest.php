<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mixpanel\DataStorage;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class DummyTest extends \PHPUnit_Framework_TestCase {
    /**
     * Dummy instance
     *
     * @var Dummy
     */
    protected $dummy;

    /**
     * Project token
     *
     * @var string
     */
    protected $projectToken = '210aa494a3c055025a2e7b0dc6112009';

    /**
     * Set up the dummy storage
     *
     */
    public function setUp() {
        $this->dummy = new Dummy();
        $this->dummy->setProjectToken($this->projectToken);
    }

    /**
     * Tear down the dummy
     */
    public function tearDown() {
        $this->dummy = null;
    }

    /**
     * Test that getState() returns empty array
     *
     * @covers Mixpanel\DataStorage\Dummy::getState
     */
    public function testGetStateReturnsEmptyArray() {
        $this->assertSame(array(), $this->dummy->getState());
    }

    /**
     * Test that getState() returns set data
     *
     * @covers Mixpanel\DataStorage\Dummy::set
     * @covers Mixpanel\DataStorage\Dummy::getState
     */
    public function testGetStateReturnsActualState() {
        $this->assertSame($this->dummy, $this->dummy->set('key', 'value'));

        $state = $this->dummy->getState();
        $this->assertSame('value', $state['key']);
    }

    /**
     * Test that storeState() returns dummy instance
     *
     * @covers Mixpanel\DataStorage\Dummy::storeState
     */
    public function testStoreStateReturnsDummy() {
        $this->assertSame($this->dummy, $this->dummy->storeState());
    }

}
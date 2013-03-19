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

use ReflectionProperty;

/**
 * Dummy storage implementation for unittesting purposes
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class DummyStorage extends StorageAbstract {

    protected $state = null;

    /**
     * {@inheritdoc}
     */
    public function getState() {
        if (is_null($this->state)) {
            $this->state = array();
        }

        return $this->filterState($this->state);
    }

    /**
     * {@inheritdoc}
     */
    public function storeState() {
        return $this;
    }

}

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class StorageAbstractTest extends \PHPUnit_Framework_TestCase {
    /**
     * Storage mock instance
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Project token
     *
     * @var string
     */
    protected $projectToken = '210aa494a3c055025a2e7b0dc6112009';

    /**
     * Set up the storage mock
     *
     */
    public function setUp() {
        $this->storage = $this->getMockForAbstractClass('Mixpanel\DataStorage\StorageAbstract');
        $this->storage->setProjectToken($this->projectToken);
    }

    /**
     * Tear down the storage mock
     */
    public function tearDown() {
        $this->storage = null;
    }

    /**
     * Test that setProjectToken() actually sets the value passed
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::setProjectToken
     */
    public function testSetAndGetProjectToken() {
        $property = new ReflectionProperty('Mixpanel\DataStorage\StorageAbstract', 'projectToken');
        $property->setAccessible(true);

        $token = 'some-other-token';
        $this->storage->setProjectToken($token);

        $this->assertSame($token, $property->getValue($this->storage));
    }

    /**
     * Test that setUserUuid() actually sets the value passed
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::setUserUuid
     */
    public function testSetAndGetUserUuid() {
        $property = new ReflectionProperty('Mixpanel\DataStorage\StorageAbstract', 'userUuid');
        $property->setAccessible(true);

        $uuid = 'some-uuid';
        $this->assertSame($this->storage, $this->storage->setUserUuid($uuid));

        $this->assertSame($uuid, $property->getValue($this->storage));
    }

    /**
     * Test that we can get and set the storage key
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::setStorageKey
     * @covers Mixpanel\DataStorage\StorageAbstract::getStorageKey
     */
    public function testGetAndSetStorageKey() {
        $key = 'some_key';
        $this->assertEquals($this->storage, $this->storage->setStorageKey($key));
        $this->assertSame($key, $this->storage->getStorageKey());
    }

    /**
     * Test that generateStorageKey creates the correct storage key
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::setUserUuid
     * @covers Mixpanel\DataStorage\StorageAbstract::setProjectToken
     * @covers Mixpanel\DataStorage\StorageAbstract::getStorageKey
     * @covers Mixpanel\DataStorage\StorageAbstract::generateStorageKey
     */
    public function testGenerateStorageKeyReturnsCorrectStorageKey() {
        $this->storage->setUserUuid(1337);
        $this->assertEquals($this->storage, $this->storage->setProjectToken('project-token'));
        $this->assertSame('mixpanel:project-token:1337', $this->storage->getStorageKey());
    }

    /**
     * Test that we can get and set the session lifetime
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::setLifetime
     * @covers Mixpanel\DataStorage\StorageAbstract::getLifetime
     */
    public function testGetAndSetLifetime() {
        $lifetime = 3600;
        $this->assertEquals($this->storage, $this->storage->setLifetime($lifetime));
        $this->assertSame($lifetime, $this->storage->getLifetime());
    }

    /**
     * Test that we can get and set keys in state
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::set
     * @covers Mixpanel\DataStorage\StorageAbstract::get
     */
    public function testGetAndSetKeysReturnSetValue() {
        $storage = new DummyStorage();

        $this->assertEquals($storage, $storage->set('key', 'value'));
        $this->assertSame('value', $storage->get('key'));
    }

    /**
     * Test that set() can overwrite old values
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::set
     * @covers Mixpanel\DataStorage\StorageAbstract::get
     */
    public function testSetCanOverwriteValues() {
        $storage = new DummyStorage();

        $this->assertEquals($storage, $storage->set('key', 'value'));
        $this->assertSame('value', $storage->get('key'));

        $this->assertEquals($storage, $storage->set('key', 'new value'));
        $this->assertSame('new value', $storage->get('key'));
    }

    /**
     * Test that add() can set unset keys
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::add
     * @covers Mixpanel\DataStorage\StorageAbstract::get
     */
    public function testAddCanSetUnsetKeys() {
        $storage = new DummyStorage();

        $this->assertEquals($storage, $storage->add('foo', 'bar'));
        $this->assertSame('bar', $storage->get('foo'));
    }

    /**
     * Test that add() cannot overwrite old values
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::set
     * @covers Mixpanel\DataStorage\StorageAbstract::add
     * @covers Mixpanel\DataStorage\StorageAbstract::get
     */
    public function testAddCannotOverwriteOldValues() {
        $storage = new DummyStorage();

        $this->assertEquals($storage, $storage->set('foo', 'bar'));
        $this->assertSame('bar', $storage->get('foo'));
        $this->assertEquals($storage, $storage->add('foo', 'new value'));
        $this->assertSame('bar', $storage->get('foo'));

        $this->assertEquals($storage, $storage->add('mix', 'panel'));
        $this->assertSame('panel', $storage->get('mix'));
        $this->assertEquals($storage, $storage->add('mix', 'new panel'));
        $this->assertSame('panel', $storage->get('mix'));
    }

    /**
     * Test that add() can overwrite old values if they match the default
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::set
     * @covers Mixpanel\DataStorage\StorageAbstract::add
     * @covers Mixpanel\DataStorage\StorageAbstract::get
     */
    public function testAddCanOverwriteDefaultValues() {
        $storage = new DummyStorage();

        $this->assertEquals($storage, $storage->set('foo', 'None'));
        $this->assertSame('None', $storage->get('foo'));
        $this->assertEquals($storage, $storage->add('foo', 'new value'));
        $this->assertSame('new value', $storage->get('foo'));

        $this->assertEquals($storage, $storage->add('age', 'undefined'));
        $this->assertSame('undefined', $storage->get('age'));
        $this->assertEquals($storage, $storage->add('age', 24, 'undefined'));
        $this->assertSame(24, $storage->get('age'));
    }

    /**
     * Test that delete() can unset keys
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::delete
     * @covers Mixpanel\DataStorage\StorageAbstract::get
     * @covers Mixpanel\DataStorage\StorageAbstract::set
     */
    public function testDeleteCanSetUnsetKeys() {
        $storage = new DummyStorage();

        $this->assertSame($storage, $storage->set('foo', 'bar'));
        $this->assertSame('bar', $storage->get('foo'));
        $this->assertEquals($storage, $storage->delete('foo'));
        $this->assertFalse($storage->get('foo'));
    }

    /**
     * Test that delete() does not fail on empty state
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::delete
     */
    public function testDeleteDoesNotFailOnEmptyState() {
        $storage = new DummyStorage();

        $this->assertEquals($storage, $storage->delete('foo'));
    }

    /**
     * Test that getting an undefined key returns false
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::get
     */
    public function testGettingUndefinedKeyReturnsFalse() {
        $storage = new DummyStorage();

        $this->assertFalse($storage->get('some key not defined in state'));
    }

    /**
     * Test that filterState removes double-underscore-prefixed keys
     *
     * @covers Mixpanel\DataStorage\StorageAbstract::filterState
     */
    public function testFilterStateRemoveUnderscoredKeys() {
        $data = array(
            'foo'         => 'bar',
            '__alias'     => 'somealias',
            '__mtp'       => 'sure',
            'distinct_id' => 'some-uuid',
        );

        $this->assertSame(array(
            'foo'         => 'bar',
            'distinct_id' => 'some-uuid'
        ), $this->storage->filterState($data));
    }

}
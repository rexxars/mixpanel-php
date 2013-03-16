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
 * User data storage interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
interface StorageInterface {

    /**
     * Set the project token
     *
     * @param  string $token
     * @return StorageInterface
     */
    function setProjectToken($token);

    /**
     * Set the users UUID
     *
     * @param  string $uuid
     * @return StorageInterface
     */
    function setUserUuid($uuid);

    /**
     * Sets the key to store the data under
     *
     * @param  string $key
     * @return StorageInterface
     */
    function setStorageKey($key);

    /**
     * Gets the key to store the data under
     *
     * @return string
     */
    function getStorageKey();

    /**
     * Sets the number of seconds this storage key should last for
     *
     * @param int $seconds Number of seconds the key should be valid for
     * @return StorageInterface
     */
    function setLifetime($seconds);

    /**
     * Gets the number of seconds this storage key should last for
     *
     * @return int Number of seconds the key should be valid for
     */
    function getLifetime();

    /**
     * Stores the specified value under the specified key.
     *
     * Must automatically call storeState on change
     *
     * @param  string $key   Key under which to store the value
     * @param  string $value Value to store
     * @return StorageInterface
     */
    function set($key, $value);

    /**
     * Stores the specified value under the specified key,
     * but only if the key does not already exist
     *
     * Must automatically call storeState on change
     *
     * @param  string $key      Key under which to store the value
     * @param  string $value    Value to store
     * @param  string $default  If the current value is this default value and
     *                          a different value is set, we will override it.
     *                          Defaults to "None".
     * @return StorageInterface
     */
    function add($key, $value, $defaultValue = 'None');

    /**
     * Returns the item stored under the specified key
     *
     * @param  string $key Key to retrieve value for
     * @return string
     */
    function get($key);

    /**
     * Deletes the specified key from storage
     *
     * Must automatically call storeState on change
     *
     * @param  string $key  The eky to delete
     * @return StorageInterface
     */
    function delete($key);

    /**
     * Get the data associated to the set storage key from storage
     *
     * Should not include double-underscored-prefixed (__*) properties
     *
     * @return array
     */
    function getState();

    /**
     * Store the internally cached state to data store
     *
     * @return StoreInterface
     */
    function storeState();

}
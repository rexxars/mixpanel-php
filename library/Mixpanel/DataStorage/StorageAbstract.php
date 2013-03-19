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
 * Data storage abstract
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
abstract class StorageAbstract implements StorageInterface {

    /**
     * Key to store data under
     *
     * @var boolean
     */
    protected $storageKey = null;

    /**
     * How long the storage key should be valid for, in seconds
     *
     * @var int
     */
    protected $lifetime = 31536000;

    /**
     * Internal cache for data state
     *
     * @var array
     */
    protected $state = null;

    /**
     * User UUID
     *
     * @var string
     */
    protected $userUuid = null;

    /**
     * Project token
     *
     * @var string
     */
    protected $projectToken = null;

    /**
     * {@inheritdoc}
     */
    public function setProjectToken($token) {
        $this->projectToken = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserUuid($uuid) {
        $this->userUuid = $uuid;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStorageKey($key) {
        $this->storageKey = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey() {
        if (is_null($this->storageKey)) {
            $this->storageKey = $this->generateStorageKey();
        }

        return $this->storageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setLifetime($seconds) {
        $this->lifetime = (int) $seconds;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLifetime() {
        return $this->lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value) {
        if (is_null($this->state)) {
            $this->getState();
        }

        $this->state[$key] = $value;
        $this->storeState();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add($key, $value, $default = 'None') {
        if (is_null($this->state)) {
            $this->getState();
        }

        if (!empty($this->state[$key]) && $this->state[$key] != $default) {
            return $this;
        }

        $this->state[$key] = $value;
        $this->storeState();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        if (is_null($this->state)) {
            $this->getState();
        }

        if (!isset($this->state[$key])) {
            return false;
        }

        return $this->state[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) {
        if (is_null($this->state)) {
            $this->getState();
        }

        if (isset($this->state[$key])) {
            unset($this->state[$key]);
            $this->storeState();
        }

        return $this;
    }

    /**
     * Filters the data state, removing unwanted properties
     *
     * @return array
     */
    public function filterState($state) {
        $newState = array();
        foreach ($state as $key => $value) {
            if (strpos($key, '__') !== 0) {
                $newState[$key] = $value;
            }
        }

        return $newState;
    }

    /**
     * Generate a storage key for this project/user
     *
     * @return string
     */
    protected function generateStorageKey() {
        return 'mixpanel:' . $this->projectToken . ':' . $this->userUuid;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getState();

    /**
     * {@inheritdoc}
     */
    abstract public function storeState();

}
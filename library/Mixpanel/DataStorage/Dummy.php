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
 * Dummy data storage - will not actually store any data
 * Use only for "server events", when you do not want to
 * connect events to a distinct user
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class Dummy extends StorageAbstract implements StorageInterface {

    /**
     * {@inheritdoc}
     */
    public function getState() {
        if (is_null($this->state)) {
            $this->state = array();
        }

        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function storeState() {
        return $this;
    }

}
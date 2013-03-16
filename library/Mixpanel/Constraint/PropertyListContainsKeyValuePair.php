<?php
/**
 * This file is part of the mixpanel-php package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mixpanel\Constraint;

/**
 * Verifies that the decoded GET-parameter "data" is valid and contains
 * a given key => value pair in its properties
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/mixpanel-php
 */
class PropertyListContainsKeyValuePair extends \PHPUnit_Framework_Constraint {

    /**
     * Key to ensure exists in property list
     *
     * @var string
     */
    protected $key;

    /**
     * Value to ensure matches
     *
     * @var string
     */
    protected $value;

    /**
     * Error message
     *
     * @var string
     */
    protected $error;

    /**
     * Constructs the constraint
     *
     * @param string $key   Key you want to ensure exists in property list
     * @param string $value Value to match for the provided key
     */
    public function __construct($key, $value) {
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * Takes a URL and verifies that it matches the constraints provided
     *
     * @param  string $other URL to decode and match
     * @return boolean
     */
    public function matches($other) {
        $urlParts = parse_url($other);

        if (!isset($urlParts['query'])) {
            $this->error = 'URL has no query-string';
            return false;
        }

        parse_str($urlParts['query'], $getParams);

        if (!isset($getParams['data'])) {
            $this->error = 'URL has no "data"-parameter';
            return false;
        }

        $json = base64_decode($getParams['data']);
        $data = json_decode($json, true);

        if (is_null($data)) {
            $this->error = 'data-parameter contained invalid JSON';
            return false;
        }

        if (!isset($data['properties'])) {
            $this->error = '"properties"-list not set';
            return false;
        }

        if (!isset($data['properties'][$this->key])) {
            $this->error = 'Properties does not contain key (' . $this->key . ')';
            return false;
        }

        $this->error = null;
        return $data['properties'][$this->key] == $this->value;
    }

    /**
     * Provide a helpful error message when constraint fails
     *
     * @return string
     */
    public function toString() {
        if ($this->error) {
            return $this->error;
        }

        return 'key (' . $this->key . ') contains expected value (' . $this->value . ')';
    }
}
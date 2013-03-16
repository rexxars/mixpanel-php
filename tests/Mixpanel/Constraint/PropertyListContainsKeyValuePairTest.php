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
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class PropertyListContainsKeyValuePairTest extends \PHPUnit_Framework_TestCase {

    /**
     * Ensure constraint fails when passing empty URL
     *
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::__construct
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::matches
     */
    public function testConstraintFailsOnEmptyUrl() {
        $constraint = new PropertyListContainsKeyValuePair('foo', 'bar');

        $this->assertFalse($constraint->matches(''));
        $this->assertSame('URL has no query-string', $constraint->toString());
    }

    /**
     * Ensure constraint fails when the URL does not contain data-parameter
     *
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::__construct
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::matches
     */
    public function testConstraintFailsOnMissingDataParameter() {
        $constraint = new PropertyListContainsKeyValuePair('foo', 'bar');

        $this->assertFalse($constraint->matches('http://tech.vg.no/?awesome=true'));
        $this->assertSame('URL has no "data"-parameter', $constraint->toString());
    }

    /**
     * Ensure constraint fails when decoded JSON is invalid
     *
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::__construct
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::matches
     */
    public function testConstraintFailsOnInvalidJson() {
        $constraint = new PropertyListContainsKeyValuePair('foo', 'bar');

        $this->assertFalse($constraint->matches('http://tech.vg.no/?data=wat'));
        $this->assertSame('data-parameter contained invalid JSON', $constraint->toString());
    }

    /**
     * Ensure constraint fails when properties array is not present
     *
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::__construct
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::matches
     */
    public function testConstraintFailsOnMissingPropertiesArray() {
        $constraint = new PropertyListContainsKeyValuePair('foo', 'bar');

        $this->assertFalse($constraint->matches('http://tech.vg.no/?data=e30='));
        $this->assertSame('"properties"-list not set', $constraint->toString());
    }

    /**
     * Ensure constraint fails when properties list does not contain given key
     *
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::__construct
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::matches
     */
    public function testConstraintFailsOnKeyDoesNotExist() {
        $constraint = new PropertyListContainsKeyValuePair('foo', 'bar');

        $this->assertFalse($constraint->matches('http://tech.vg.no/?data=eyJwcm9wZXJ0aWVzIjp7ImEiOjF9fQ=='));
        $this->assertSame('Properties does not contain key (foo)', $constraint->toString());
    }

    /**
     * Ensure constraint fails when the value for the given key does not match
     *
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::__construct
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::matches
     */
    public function testConstraintFailsOnWrongValue() {
        $constraint = new PropertyListContainsKeyValuePair('a', '2');

        $this->assertFalse($constraint->matches('http://tech.vg.no/?data=eyJwcm9wZXJ0aWVzIjp7ImEiOjF9fQ=='));
        $this->assertSame('key (a) contains expected value (2)', $constraint->toString());
    }

    /**
     * Ensure constraint passes when the value for the given key matches expected value
     *
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::__construct
     * @matches Mixpanel\Constraint\PropertyListContainsKeyValuePair::matches
     */
    public function testConstraintPassesOnCorrectValue() {
        $constraint = new PropertyListContainsKeyValuePair('a', 1);
        $this->assertTrue($constraint->matches('http://tech.vg.no/?data=eyJwcm9wZXJ0aWVzIjp7ImEiOjF9fQ=='));

        $constraint = new PropertyListContainsKeyValuePair('foo', 'bar');
        $this->assertTrue($constraint->matches('http://tech.vg.no/?data=eyJwcm9wZXJ0aWVzIjp7ImZvbyI6ImJhciJ9fQ'));
    }

}
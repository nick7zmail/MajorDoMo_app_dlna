<?php
/**
 * This file is part of the Urn project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\UrnTest;

use GravityMedia\Urn\Urn;

/**
 * URN test
 *
 * @package GravityMedia\UrnTest
 */
class UrnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers GravityMedia\Urn\Urn::toString()
     */
    public function testStringExtraction()
    {
        $urn = new Urn();
        $urn->setNamespaceIdentifier('this-is-an-example');
        $urn->setNamespaceSpecificString('t(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e');

        $this->assertEquals('urn:this-is-an-example:t(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e', $urn->toString());
    }

    /**
     * @covers GravityMedia\Urn\Urn::fromString()
     */
    public function testStringHydration()
    {
        $urn = Urn::fromString('urn:this-is-an-example:t(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e');

        $this->assertInstanceOf('GravityMedia\Urn\Urn', $urn);
    }

    /**
     * @covers GravityMedia\Urn\Urn::equals()
     */
    public function testEqualityOperator()
    {
        $urn = Urn::fromString('urn:this-is-an-example:t(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e');

        $this->assertTrue($urn->equals($urn));
    }

    /**
     * @covers GravityMedia\Urn\Urn::isValid()
     */
    public function testValidator()
    {
        $this->assertTrue(Urn::isValid('urn:this-is-an-example:t(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e'));
    }
}

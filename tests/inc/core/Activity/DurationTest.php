<?php

namespace Runalyze\Activity;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-12-06 at 11:08:33.
 */
class DurationTest extends \PHPUnit_Framework_TestCase {

	public function testFromSeconds() {
		$Time = new Duration(17.23);

		$this->assertEquals(17.23, $Time->seconds());
		$this->assertEquals(127, $Time->fromSeconds(127)->seconds());
	}

	public function testFromString() {
		$Time = new Duration("1:17:21");

		$this->assertEquals(1*3600 + 17*60 + 21, $Time->seconds());
		$this->assertEquals(17*60 + 21, $Time->fromString("17:21")->seconds());
		$this->assertEquals(3*60 + 54.2, $Time->fromString("3:54,2")->seconds());
		$this->assertEquals(12, $Time->fromString("0:12")->seconds());
		$this->assertEquals(10.05, $Time->fromString("10.05")->seconds());
	}

	public function testAddAndSubtract() {
		$Time = new Duration(100);

		$this->assertEquals(200, $Time->add(new Duration(100))->seconds());
		$this->assertEquals(150, $Time->subtract(new Duration(50))->seconds());
	}

	public function testMultiply() {
		$Time = new Duration(10);

		$this->assertEquals(20, $Time->multiply(2)->seconds());
	}

	public function testIsFlags() {
		$Time = new Duration();

		$this->assertTrue($Time->isZero());
		$this->assertFalse($Time->isNegative());

		$Time->fromSeconds(-10);

		$this->assertFalse($Time->isZero());
		$this->assertTrue($Time->isNegative());
	}

	public function testAutoFormat() {
		$Time = new Duration();

		$this->assertEquals('1d 09:13:41', $Time->fromString('1d 09:13:41,4')->string());
		$this->assertEquals('1d 09:13:41', $Time->fromString('33:13:41,4')->string());
		$this->assertEquals(   '23:13:41', $Time->fromString('23:13:41,4')->string());
		$this->assertEquals(    '3:13:41', $Time->fromString(' 3:13:41,4')->string());
		$this->assertEquals(      '13:41,40', $Time->fromString('13:41,4')->string());
		$this->assertEquals(       '3:41,40', $Time->fromString(' 3:41,4')->string());
		$this->assertEquals(       '0:41,40', $Time->fromString('   41,4')->string());
		$this->assertEquals(      '13:41', $Time->fromString('13:41')->string());
		$this->assertEquals(       '3:41', $Time->fromString(' 3:41')->string());
		$this->assertEquals(       '0:41', $Time->fromString('   41')->string());
	}

	public function testFormat() {
		$Time = new Duration();

		$this->assertEquals('13.3', $Time->fromString('13.27')->string('s.u', 1));
		$this->assertEquals('0d 00:00:27.000', $Time->fromString('27')->string('z\d H:i:s.u', 3));
		$this->assertEquals('0d 01:00:01', $Time->fromString('3601')->string('z\d H:i:s'));
		$this->assertEquals('00:01', $Time->fromString('3601')->string('i:s'));
	}

	public function testStaticMethod() {
		$this->assertEquals('1d 09:13:41', Duration::format('33:13:41,4'));
	}

	public function testExactLimits() {
		$Time = new Duration();

		$this->assertEquals('1d 00:00:00', $Time->fromString('24:00:00')->string());
		$this->assertEquals(   '10:00:00', $Time->fromString('10:00:00')->string());
		$this->assertEquals(    '1:00:00', $Time->fromString(' 1:00:00')->string());
		$this->assertEquals(      '10:00', $Time->fromString('   10:00')->string());
		$this->assertEquals(       '1:00', $Time->fromString('    1:00')->string());
	}

	public function testStrangeResults() {
		$Time = new Duration();

		$this->assertEquals('1:21', $Time->fromSeconds(162*0.2/0.4)->string(Duration::FORMAT_AUTO, 1));
		$this->assertEquals('1:26', $Time->fromSeconds(172*0.2/0.4)->string(Duration::FORMAT_AUTO, 1));
	}

	public function testCompetitionResults() {
		$Time = new Duration();

		$this->assertEquals('8,18s', $Time->fromSeconds(8.18)->string(Duration::FORMAT_COMPETITION, 2));
		$this->assertEquals('13,00s', $Time->fromSeconds(13.00)->string(Duration::FORMAT_COMPETITION, 2));
		$this->assertEquals('1:03', $Time->fromSeconds(63.00)->string(Duration::FORMAT_COMPETITION, 2));
	}

	public function testHourFormat() {
		$Time = new Duration();

		$this->assertEquals('24:00:00', $Time->fromSeconds(24*3600)->string(Duration::FORMAT_WITH_HOURS));
		$this->assertEquals('27:13:08', $Time->fromSeconds(27*3600+13*60+8)->string(Duration::FORMAT_WITH_HOURS));
	}

	public function testEmptyTime() {
		$Time = new Duration(0);

		$this->assertEquals('0:00', $Time->string(Duration::FORMAT_AUTO));
		$this->assertEquals('0:00', $Time->string(Duration::FORMAT_COMPETITION));
		$this->assertEquals('0:00:00', $Time->string(Duration::FORMAT_WITH_HOURS));
		$this->assertEquals('0d 00:00:00', $Time->string(Duration::FORMAT_WITH_DAYS));
	}

}

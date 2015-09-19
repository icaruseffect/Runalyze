<?php

require_once FRONTEND_PATH.'../plugin/RunalyzePluginTool_DbBackup/class.RunalyzeBulkInsert.php';
require_once FRONTEND_PATH.'../plugin/RunalyzePluginTool_DbBackup/class.RunalyzeJsonImporterResults.php';
require_once FRONTEND_PATH.'../plugin/RunalyzePluginTool_DbBackup/class.RunalyzeJsonImporter.php';

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-09-25 at 16:47:03.
 */
class RunalyzeJsonImporterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var RunalyzeJsonImporter
	 */
	protected $object;

	/**
	 * @var PDOforRunalyze
	 */
	protected $DB;

	/**
	 * @var int
	 */
	protected $AccountID;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->DB = DB::getInstance();
		$this->truncateTables();

		$_POST = array();
		$this->AccountID = 1;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		$this->truncateTables();

		$_POST = array();
	}

	private function truncateTables() {
		$this->DB->exec('DELETE FROM `runalyze_training`');
		$this->DB->exec('DELETE FROM `runalyze_equipment_type`');
		$this->DB->exec('DELETE FROM `runalyze_user`');

		$this->DB->exec('DELETE FROM `runalyze_conf` WHERE `key`="TEST_CONF"');
		$this->DB->exec('DELETE FROM `runalyze_dataset` WHERE `name`="test-dataset"');
		$this->DB->exec('DELETE FROM `runalyze_plugin` WHERE `key`="RunalyzePluginTool_TEST"');
		$this->DB->exec('DELETE FROM `runalyze_plugin_conf` WHERE `config`="test_one"');
		$this->DB->exec('DELETE FROM `runalyze_plugin_conf` WHERE `config`="test_two"');

		$this->DB->exec('DELETE FROM `runalyze_sport`');
		$this->DB->exec('DELETE FROM `runalyze_type`');
	}

	/**
	 * Fill dummy values
	 */
	private function fillDummyTrainings() {
		$this->DB->insert('training', array('sportid', 'time', 'distance'), array(1, time() - DAY_IN_S, 15) );
		$this->DB->insert('training', array('sportid', 'time', 'distance'), array(2, time(), 10) );

		return 2;
	}

	private function fillDummyUser() {
		$this->DB->insert('user', array('time', 'weight'), array(time() - DAY_IN_S, 72) );
		$this->DB->insert('user', array('time', 'weight'), array(time(), 70) );

		return 2;
	}

	/**
	 * Test deletes
	 */
	public function testDeleteActivities() {
		$_POST['delete_trainings'] = true;

		$numTrainings = $this->fillDummyTrainings();
		$numUser = $this->fillDummyUser();

		$this->assertEquals($numTrainings, $this->DB->query('SELECT COUNT(*) FROM `runalyze_training`')->fetchColumn());
		$this->assertEquals($numUser, $this->DB->query('SELECT COUNT(*) FROM `runalyze_user`')->fetchColumn());

		$Importer = new RunalyzeJsonImporter('../tests/testfiles/backup/default-empty.json.gz');
		$Importer->importData();

		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `runalyze_training`')->fetchColumn());
		$this->assertEquals($numUser, $this->DB->query('SELECT COUNT(*) FROM `runalyze_user`')->fetchColumn());
	}

	public function testDeleteBody() {
		$_POST['delete_user_data'] = true;

		$numTrainings = $this->fillDummyTrainings();
		$numUser = $this->fillDummyUser();

		$this->assertEquals($numTrainings, $this->DB->query('SELECT COUNT(*) FROM `runalyze_training`')->fetchColumn());
		$this->assertEquals($numUser, $this->DB->query('SELECT COUNT(*) FROM `runalyze_user`')->fetchColumn());

		$Importer = new RunalyzeJsonImporter('../tests/testfiles/backup/default-empty.json.gz');
		$Importer->importData();

		$this->assertEquals($numTrainings, $this->DB->query('SELECT COUNT(*) FROM `runalyze_training`')->fetchColumn());
		$this->assertEquals(0, $this->DB->query('SELECT COUNT(*) FROM `runalyze_user`')->fetchColumn());
	}

	/**
	 * Test updates
	 */
	public function testUpdates() {
		$_POST['overwrite_config'] = true;
		$_POST['overwrite_dataset'] = true;
		$_POST['overwrite_plugin'] = true;

		$this->DB->insert('conf', array('category', 'key', 'value'), array('test-data', 'TEST_CONF', 'false') );
		$this->DB->insert('dataset', array('name', 'class', 'style', 'position', 'summary'), array('test-dataset', '', 'width:10px;', 3, 0) );
		$id = $this->DB->insert('plugin', array('key', 'active', 'order'), array('RunalyzePluginTool_TEST', 0, 3) );
		$this->DB->insert('plugin_conf', array('pluginid', 'config', 'value'), array($id, 'test_one', 2) );
		$this->DB->insert('plugin_conf', array('pluginid', 'config', 'value'), array($id, 'test_two', 1) );

		// Act
		$Importer = new RunalyzeJsonImporter('../tests/testfiles/backup/default-update.json.gz');
		$Importer->importData();

		// Assert
		$this->assertEquals('true', $this->DB->query('SELECT `value` FROM `runalyze_conf` WHERE `key`="TEST_CONF" LIMIT 1')->fetchColumn());

		$Dataset = $this->DB->query('SELECT * FROM `runalyze_dataset` WHERE `name`="test-dataset" LIMIT 1')->fetch();
		$this->assertEquals('testclass', $Dataset['class']);
		$this->assertEquals('', $Dataset['style']);
		$this->assertEquals('42', $Dataset['position']);
		$this->assertEquals('1', $Dataset['summary']);

		$Plugin = $this->DB->query('SELECT * FROM `runalyze_plugin` WHERE `key`="RunalyzePluginTool_TEST" LIMIT 1')->fetch();
		$this->assertEquals('1', $Plugin['active']);
		$this->assertEquals('42', $Plugin['order']);

		$this->assertEquals('1', $this->DB->query('SELECT `value` FROM `runalyze_plugin_conf` WHERE `config`="test_one" LIMIT 1')->fetchColumn());
		$this->assertEquals('2', $this->DB->query('SELECT `value` FROM `runalyze_plugin_conf` WHERE `config`="test_two" LIMIT 1')->fetchColumn());
	}

	/**
	 * Test inserts
	 */
	public function testInserts() {
		$TestSport = $this->DB->insert('runalyze_sport', array('name'), array('Testsport') );
		$TestType = $this->DB->insert('runalyze_type', array('name', 'sportid'), array('Testtype', $TestSport) );

		// Act
		$Importer = new RunalyzeJsonImporter('../tests/testfiles/backup/default-insert.json.gz');
		$Importer->importData();

		// Check nothing changed
		$this->assertEquals($TestSport, $this->DB->query('SELECT `id` FROM `runalyze_sport` WHERE `name`="Testsport"')->fetchColumn());
		$this->assertEquals($TestType, $this->DB->query('SELECT `id` FROM `runalyze_type` WHERE `name`="Testtype"')->fetchColumn());

		// Check existing/new
		$NewSport = $this->DB->query('SELECT `id` FROM `runalyze_sport` WHERE `name`="Newsport"')->fetchColumn();
		$NewType = $this->DB->query('SELECT `id` FROM `runalyze_type` WHERE `name`="Newtype"')->fetchColumn();

		$this->assertNotEquals(0, $NewSport);
		$this->assertNotEquals(0, $NewType);

		// Check inserts
		$this->assertEquals(array(
			'time'			=> '1234567890',
			'weight'		=> '70',
			'pulse_rest'	=> '45',
			'pulse_max'		=> '205'
		), $this->DB->query('SELECT `time`, `weight`, `pulse_rest`, `pulse_max` FROM `runalyze_user` WHERE `time`="1234567890" LIMIT 1')->fetch());

		$this->assertEquals(array(
			'time'		=> '1234567890',
			'sportid'	=> $TestSport,
			'typeid'	=> $TestType,
			's'			=> '900.00'
		), $this->DB->query('SELECT `time`, `sportid`, `typeid`, `s` FROM `runalyze_training` WHERE `comment`="UNITTEST-1" LIMIT 1')->fetch());

		$this->assertEquals(array(
			'time'		=> '1234567890',
			'sportid'	=> $NewSport,
			'typeid'	=> $NewType,
			's'			=> '1500.00'
		), $this->DB->query('SELECT `time`, `sportid`, `typeid`, `s` FROM `runalyze_training` WHERE `comment`="UNITTEST-2" LIMIT 1')->fetch());
	}

	/**
	 * Test with equipment
	 */
	public function testWithEquipment() {
		$Importer = new RunalyzeJsonImporter('../tests/testfiles/backup/with-equipment.json.gz', $this->AccountID);
		$Importer->importData();

		$SportA = $this->DB->query('SELECT `id` FROM `runalyze_sport` WHERE `name`="Sport A"')->fetchColumn();
		$SportB = $this->DB->query('SELECT `id` FROM `runalyze_sport` WHERE `name`="Sport B"')->fetchColumn();

		$TypeA = $this->DB->query('SELECT `id` FROM `runalyze_equipment_type` WHERE `name`="Typ A"')->fetchColumn();
		$TypeAB = $this->DB->query('SELECT `id` FROM `runalyze_equipment_type` WHERE `name`="Typ AB"')->fetchColumn();

		$Activity1 = $this->DB->query('SELECT `id` FROM `runalyze_training` WHERE `comment`="UNITTEST-1"')->fetchColumn();
		$Activity2 = $this->DB->query('SELECT `id` FROM `runalyze_training` WHERE `comment`="UNITTEST-2"')->fetchColumn();
		$Activity3 = $this->DB->query('SELECT `id` FROM `runalyze_training` WHERE `comment`="UNITTEST-3"')->fetchColumn();

		$EquipmentA1 = $this->DB->query('SELECT `id` FROM `runalyze_equipment` WHERE `name`="A1"')->fetchColumn();
		$EquipmentAB1 = $this->DB->query('SELECT `id` FROM `runalyze_equipment` WHERE `name`="AB1"')->fetchColumn();
		$EquipmentAB2 = $this->DB->query('SELECT `id` FROM `runalyze_equipment` WHERE `name`="AB2"')->fetchColumn();

		$this->assertEquals(array(
			array($SportA, $TypeA),
			array($SportA, $TypeAB),
			array($SportB, $TypeAB)
		), $this->DB->query('SELECT `sportid`, `equipment_typeid` FROM `runalyze_equipment_sport`')->fetchAll(PDO::FETCH_NUM));

		$this->assertEquals($TypeA, $this->DB->query('SELECT `typeid` FROM `runalyze_equipment` WHERE `name`="A1"')->fetchColumn());
		$this->assertEquals($TypeAB, $this->DB->query('SELECT `typeid` FROM `runalyze_equipment` WHERE `name`="AB1"')->fetchColumn());
		$this->assertEquals($TypeAB, $this->DB->query('SELECT `typeid` FROM `runalyze_equipment` WHERE `name`="AB2"')->fetchColumn());

		$this->assertEquals(array($EquipmentA1), $this->DB->query('SELECT `equipmentid` FROM `runalyze_activity_equipment` WHERE `activityid`='.$Activity1)->fetchAll(PDO::FETCH_COLUMN));
		$this->assertEquals(array($EquipmentA1, $EquipmentAB1, $EquipmentAB2), $this->DB->query('SELECT `equipmentid` FROM `runalyze_activity_equipment` WHERE `activityid`='.$Activity2)->fetchAll(PDO::FETCH_COLUMN));
		$this->assertEquals(array($EquipmentAB1), $this->DB->query('SELECT `equipmentid` FROM `runalyze_activity_equipment` WHERE `activityid`='.$Activity3)->fetchAll(PDO::FETCH_COLUMN));
	}

}

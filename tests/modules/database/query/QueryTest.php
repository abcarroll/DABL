<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../config.php';

class QueryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @group count
	 * @covers Query::getQuery
	 */
	function testGetQueryCountAggregateFunctionsWithoutGroup() {
		$q = new Query('test_table');
		$q->addColumn('count(0)');
		$q->addColumn('sum(2)');
		$q->setAction(Query::ACTION_COUNT);
		$count_q = $q->getQuery()->__toString();

		// if there is an aggregate function and no group by, the
		$q->setAction(Query::ACTION_SELECT);
		$select_q = $q->getQuery()->__toString();

		$this->assertEquals("SELECT count(0)\nFROM ($select_q) a", $count_q);


		$q = new Query('test_table');
		$q->addColumn('count(0)');
		$q->addColumn('sum(2)');
		$q->addColumn('not_allowed_in_sql_server');
		$q->setAction(Query::ACTION_COUNT);
		$count_q = $q->getQuery()->__toString();

		// if there is an aggregate function and no group by, the
		$q->setAction(Query::ACTION_SELECT);
		$select_q = $q->getQuery()->__toString();

		$this->assertNotEquals("SELECT count(0)\nFROM ($select_q) a", $count_q);
	}

	/**
	 * @group count
	 * @covers Query::getQuery
	 */
	function testGetQueryCountWithGroupNoColumns() {
		$q = new Query('test_table');
		$q->addGroup('test_column');
		$q->setAction(Query::ACTION_COUNT);
		$count_q = $q->getQuery()->__toString();

		$q->setAction(Query::ACTION_SELECT);
		// to count the rows in an agggregate query, the inner query should
		// use the group by columns as the select columns
		$q->setColumns(array('test_column'));
		$select_q = $q->getQuery()->__toString();

		$this->assertEquals("SELECT count(0)\nFROM ($select_q) a", $count_q);
	}

	/**
	 * @group count
	 * @covers Query::getQuery
	 */
	function testGetQueryCount() {
		$q = new Query('test_table');
		$q->setAction(Query::ACTION_COUNT);
		$count_q = $q->getQuery()->__toString();
		$this->assertEquals("SELECT count(0)\nFROM `test_table`", $count_q);
	}

	/**
	 * @group group
	 * @covers Query::getQuery
	 */
	function testGroupBy() {
		$q = new Query('test_table');
		$q->addGroup('functiontastic()');
		$q->addGroup('columntastic');
		$q->addGroup('table.columntastic');
		$q_string = $q->getQuery()->__toString();
		$this->assertEquals("SELECT `test_table`.*\nFROM `test_table`\nGROUP BY functiontastic(), `columntastic`, `table`.`columntastic`", $q_string);
	}

	/**
	 * @group subquery
	 * @covers Query::getTable
	 */
	function testGetTableSingleWordWithAlias() {
		$q = new Query('testing', 'alias');
		$this->assertEquals('testing', $q->getTable());
	}

	/**
	 * @group subquery
	 * @covers Query::getTable
	 */
	function testGetTableOneParamSingleWordWithAlias() {
		$q = new Query('testing AS alias');
		$this->assertEquals('testing', $q->getTable());
		$this->assertEquals('alias', $q->getAlias());
	}

	/**
	 * @group subquery
	 * @covers Query::getTable
	 */
	function testAddJoinWithAlias() {
		$q = new Query('table');
		$q->addJoin('testing AS alias', 'alias.column = table.column');
		foreach ($q->getJoins() as $join) {
			$this->assertEquals('testing', $join->getTable());
			$this->assertEquals('alias', $join->getAlias());
			break;
		}
		$this->assertEquals("SELECT `table`.*\nFROM `table`\n\tJOIN `testing` AS alias ON (alias.column = table.column)", (string) $q->getQuery());
	}

	/**
	 * @group subquery
	 * @covers Query::getTable
	 */
	function testGetTableTwoWordsWithAlias() {
		$q = new Query('SELECT testing', 'alias');
		$this->assertEquals('SELECT testing', $q->getTable());
	}

	/**
	 * @group subquery
	 * @covers Query::getQuery
	 */
	function testGetQueryTwoWordsWithAlias() {
		$q = new Query('SELECT testing', 'alias');
		$this->assertEquals("SELECT alias.*\nFROM SELECT testing AS alias", (string) $q->getQuery());
	}

	/**
	 * @group subquery
	 * @covers Query::getTable
	 */
	function testGetTableSingleWordNoAlias() {
		$q = new Query('testing');
		$this->assertEquals('testing', $q->getTable());
	}

	/**
	 * @group subquery
	 * @covers Query::getAlias
	 */
	function testGetAliasSingleWordWithAlias() {
		$q = new Query('testing', 'alias');
		$this->assertEquals('alias', $q->getAlias());
	}

	/**
	 * @group subquery
	 * @covers Query::getAlias
	 */
	function testGetAliasTwoWordsWithAlias() {
		$q = new Query('SELECT testing', 'alias');
		$this->assertEquals('alias', $q->getAlias());
	}

	/**
	 * @group subquery
	 * @covers Query::getAlias
	 */
	function testGetAliasSingleWordNoAlias() {
		$q = new Query('testing');
		$this->assertNull($q->getAlias());
	}
}

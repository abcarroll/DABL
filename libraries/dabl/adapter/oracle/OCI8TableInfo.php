<?php

/**
 * Oracle (OCI8) implementation of TableInfo.
 *
 * @author	David Giffin <david@giffin.org>
 * @author	Hans Lellelid <hans@xmpl.org>
 * @version   $Revision$
 * @package   creole.drivers.oracle.metadata
 */
class OCI8TableInfo extends TableInfo {

	private $schema;

	function __construct(OCI8DatabaseInfo $database, $name) {
		$this->schema = strtoupper( $database->getSchema() );
		parent::__construct($database, $name);
		$this->name = strtoupper( $this->name );
	}

	/** Loads the columns for this table. */
	protected function initColumns() {
		// To get all of the attributes we need, we'll actually do
		// two separate queries.  The first gets names and default values
		// the second will fill in some more details.

		$sql = "
			SELECT column_name
				, data_type
				, data_precision
				, data_length
				, data_default
				, nullable 
				, data_scale
			FROM  all_tab_columns
			WHERE table_name = '{$this->name}'
				AND OWNER = '{$this->schema}'";

		$result = $this->getDatabase()->getConnection()->query($sql);

		while ( $row = $result->fetch() ) {
			$row = array_change_key_case($row, CASE_LOWER);
			$this->columns[$row['column_name']] = new ColumnInfo( $this, $row['column_name'], OCI8Types::getType($row['data_type']), $row['data_type'], $row['data_length'], $row['data_precision'], $row['data_scale'], $row['nullable'], $row['data_default'] );
		}

		$this->colsLoaded = true;
	}

	/** Loads the primary key information for this table. */
	protected function initPrimaryKey() {
		// columns have to be loaded first
		if (!$this->colsLoaded) $this->initColumns();


		// Primary Keys Query
		$sql = "SELECT a.owner, a.table_name,
							a.constraint_name, a.column_name
						FROM all_cons_columns a, all_constraints b
						WHERE b.constraint_type = 'P'
						AND a.constraint_name = b.constraint_name
						AND b.table_name = '{$this->name}'
			AND b.owner = '{$this->schema}'
				";

		$result = $this->getDatabase()->getConnection()->query($sql);

		while ( $row = $result->fetch() ) {
			$row = array_change_key_case($row,CASE_LOWER);

			$name = $row['column_name'];

			if (!isset($this->primaryKey)) {
				$this->primaryKey = new PrimaryKeyInfo($name);
			}

			$this->primaryKey->addColumn($this->columns[$name]);
		}

		$this->pkLoaded = true;
	}

	/** Loads the indexes for this table. */
	protected function initIndexes() {
		// columns have to be loaded first
		if (!$this->colsLoaded) $this->initColumns();

		// Indexes
		$sql = "SELECT
			allind.index_name,
			allind.table_name,
			allind.index_type,
			allind.uniqueness,
			indcol.column_name
			FROM all_indexes allind INNER JOIN all_ind_columns indcol
				ON allind.owner = indcol.index_owner
				AND allind.index_name = indcol.index_name
			WHERE allind.table_owner = '{$this->schema}'
			AND allind.table_name = '{$this->name}'
			AND allind.index_name NOT IN (SELECT
					constraint_name
					FROM all_constraints
					WHERE constraint_type = 'P')
			ORDER BY allind.index_name,
				indcol.column_position";

		$result = $this->getDatabase()->getConnection()->query($sql);

		// Loop through the returned results, grouping the same key_name together
		// adding each column for that key.
		while ( $row = $result->fetch() ) {
			$row = array_change_key_case($row,CASE_LOWER);

			$name = $row['index_name'];
			$index_col_name = $row['column_name'];

			if (!isset($this->indexes[$name])) {
				$this->indexes[$name] = new IndexInfo($name);
			}

			$this->indexes[$name]->addColumn($this->columns[ $index_col_name ]);
		}


		$this->indexesLoaded = true;
	}

	/** Load foreign keys */
	protected function initForeignKeys() {
		// columns have to be loaded first
		if (!$this->colsLoaded) $this->initColumns();

		// Foreign keys
		// TODO resolve cross schema references
		// use all_cons... to do so, however, very slow queries then
		// optimizations are very ugly
		$sql					= "
			SELECT a.owner AS local_owner
				, a.table_name AS local_table
				, c.column_name AS local_column
				, a.constraint_name AS foreign_key_name
				, b.owner AS foreign_owner
				, b.table_name AS foreign_table
				, d.column_name AS foreign_column
				, b.constraint_name AS foreign_constraint_name
				, a.delete_rule AS on_delete
			FROM user_constraints a
				, user_constraints b
				, user_cons_columns c
				, user_cons_columns d
			WHERE a.r_constraint_name = b.constraint_name
				AND c.constraint_name = a.constraint_name
				AND d.constraint_name = b.constraint_name
				AND a.r_owner = b.owner
				AND a.constraint_type='R'
				AND a.table_name = '{$this->name}'
				AND a.owner = '{$this->schema}'
				";

		$result = $this->getDatabase()->getConnection()->query($sql);

		// Loop through the returned results, grouping the same key_name
		// together adding each column for that key.

		while ( $row = $result->fetch() ) {
			$row = array_change_key_case($row,CASE_LOWER);

			$name = $row['foreign_key_name'];

			$foreignTable = $this->database->getTable($row['foreign_table']);
			$foreignColumn = $foreignTable->getColumn($row['foreign_column']);

			$localTable   = $this->database->getTable($row['local_table']);
			$localColumn   = $localTable->getColumn($row['local_column']);

			if (!isset($this->foreignKeys[$name])) {
				$this->foreignKeys[$name] = new ForeignKeyInfo($name);
			}

			switch ( $row[ 'on_delete' ] ) {
				case 'CASCADE':
					$onDelete	= ForeignKeyInfo::CASCADE;
					break;

				case 'SET NULL':
					$onDelete	= ForeignKeyInfo::SETNULL;
					break;

				default:
				case 'NO ACTION':
					$onDelete	= ForeignKeyInfo::NONE;
					break;
			}

			// addReference( local, foreign, onDelete, onUpdate )
			// Oracle doesn't support 'on update'
			$this->foreignKeys[ $name ]->addReference(
					$localColumn
					, $foreignColumn
					, $onDelete
			);
		}

		$this->fksLoaded = true;
	}

}

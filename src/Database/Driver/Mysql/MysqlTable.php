<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Database\Driver\Mysql;

use Windwalker\Database\Command\DatabaseTable;
use Windwalker\Query\Mysql\MysqlQueryBuilder;

/**
 * Class MysqlTable
 *
 * @since 1.0
 */
class MysqlTable extends DatabaseTable
{
	/**
	 * A cache to store Table columns.
	 *
	 * @var array
	 */
	protected static $columnCache = array();

	/**
	 * rename
	 *
	 * @param string $newName
	 *
	 * @return  $this
	 */
	public function rename($newName)
	{
		$this->db->setQuery('RENAME TABLE ' . $this->db->quoteName($this->table) . ' TO ' . $this->db->quoteName($newName));

		$this->db->execute();

		return $this;
	}

	/**
	 * Locks a table in the database.
	 *
	 * @return  static  Returns this object to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function lock()
	{
		$this->db->setQuery('LOCK TABLES ' . $this->db->quoteName($this->table) . ' WRITE');

		return $this;
	}

	/**
	 * unlock
	 *
	 * @return  static  Returns this object to support chaining.
	 *
	 * @throws  \RuntimeException
	 */
	public function unlock()
	{
		$this->db->setQuery('UNLOCK TABLES')->execute();

		return $this;
	}

	/**
	 * Method to truncate a table.
	 *
	 * @return  static
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function truncate()
	{
		$this->db->setQuery('TRUNCATE TABLE ' . $this->db->quoteName($this->table))->execute();

		return $this;
	}

	/**
	 * Get table columns.
	 *
	 * @param bool $refresh
	 *
	 * @return  array Table columns with type.
	 */
	public function getColumns($refresh = false)
	{
		if (empty(static::$columnCache) || $refresh)
		{
			static::$columnCache = array_keys($this->getColumnDetails());
		}

		return static::$columnCache;
	}

	/**
	 * getColumnDetails
	 *
	 * @param bool $full
	 *
	 * @return  mixed
	 */
	public function getColumnDetails($full = true)
	{
		$query = MysqlQueryBuilder::showTableColumns($this->table, $full);

		return $this->db->setQuery($query)->loadAll('Field');
	}

	/**
	 * getColumnDetail
	 *
	 * @param string $column
	 * @param bool   $full
	 *
	 * @return  mixed
	 */
	public function getColumnDetail($column, $full = true)
	{
		$query = MysqlQueryBuilder::showTableColumns($this->table, $full, 'Field = ' . $this->db->quote($column));

		return $this->db->setQuery($query)->loadOne();
	}

	/**
	 * addColumn
	 *
	 * @param string $name
	 * @param string $type
	 * @param bool   $unsigned
	 * @param bool   $notNull
	 * @param string $default
	 * @param null   $position
	 * @param string $comment
	 *
	 * @return  mixed
	 */
	public function addColumn($name, $type = 'text', $unsigned = false, $notNull = false, $default = '', $position = null, $comment = '')
	{
		$query = MysqlQueryBuilder::addColumn($this->table, $name, $type, $unsigned, $notNull, $default, $position, $comment);

		return $this->db->setQuery($query)->execute();
	}

	/**
	 * dropColumn
	 *
	 * @param string $name
	 *
	 * @return  mixed
	 */
	public function dropColumn($name)
	{
		$query = MysqlQueryBuilder::dropColumn($name);

		return $this->db->setQuery($query)->execute();
	}

	/**
	 * addIndex
	 *
	 * @param string  $type
	 * @param string  $name
	 * @param array   $columns
	 * @param string  $comment
	 *
	 * @return  mixed
	 */
	public function addIndex($type, $name = null, $columns = array(), $comment = null)
	{
		$query = MysqlQueryBuilder::addIndex($this->table, $type, $name, $columns, $comment);

		return $this->db->setQuery($query)->execute();
	}

	/**
	 * dropIndex
	 *
	 * @param string  $type
	 * @param string  $name
	 *
	 * @return  mixed
	 */
	public function dropIndex($type, $name)
	{
		$query = MysqlQueryBuilder::dropIndex($this->table, $type, $name);

		return $this->db->setQuery($query)->execute();
	}

	/**
	 * getIndexes
	 *
	 * @return  mixed
	 */
	public function getIndexes()
	{
		// Get the details columns information.
		$this->db->setQuery('SHOW KEYS FROM ' . $this->db->quoteName($this->table));

		return $this->db->loadAll();
	}
}
 
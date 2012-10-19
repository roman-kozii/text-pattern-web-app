<?php


/**
 * Database abstraction layer.
 *
 * @package DB
 */

if (!defined('PFX'))
{
	/**
	 * Database table prefix.
	 */

	define('PFX', !empty($txpcfg['table_prefix']) ? $txpcfg['table_prefix'] : '');
}

if (version_compare(PHP_VERSION, '5.3.0') < 0)
{
	// We are deliberately using a deprecated function for PHP 4 compatibility.
	if (get_magic_quotes_runtime())
	{
		set_magic_quotes_runtime(0);
	}
}

/**
 * Initialises a database connection.
 *
 * @package DB
 */

class DB
{
	/**
	 * The database server.
	 *
	 * @var string
	 */

	public $host;

	/**
	 * The database name.
	 *
	 * @var string
	 */

	public $db;

	/**
	 * The username.
	 *
	 * @var string
	 */

	public $user;

	/**
	 * The password.
	 *
	 * @var string
	 */

	public $pass;

	/**
	 * Database client flags.
	 *
	 * @var int
	 */

	public $client_flags;

	/**
	 * The database link identifier.
	 *
	 * @var resource
	 */

	public $link;

	/**
	 * Database server version.
	 *
	 * @var string
	 */

	public $version;

	/**
	 * Creates a new link.
	 */

	public function __construct()
	{
		global $txpcfg;

		$this->host = $txpcfg['host'];
		$this->db	= $txpcfg['db'];
		$this->user = $txpcfg['user'];
		$this->pass = $txpcfg['pass'];
		$this->client_flags = isset($txpcfg['client_flags']) ? $txpcfg['client_flags'] : 0;

		$this->link = @mysql_connect($this->host, $this->user, $this->pass, false, $this->client_flags);
		if (!$this->link)
		{
			die(db_down());
		}

		$this->version = mysql_get_server_info();

		if (!$this->link)
		{
			$GLOBALS['connected'] = false;
		}
		else
		{
			$GLOBALS['connected'] = true;
		}
		@mysql_select_db($this->db) or die(db_down());

		$version = $this->version;
		// Be backwards compatible.
		if (isset($txpcfg['dbcharset']) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)))
		{
			mysql_query("SET NAMES ". $txpcfg['dbcharset']);
		}
	}
}

/**
 * Current database link.
 *
 * @access private
 * @global DB $DB
 */

$DB = new DB;

/**
 * Prefixes a database table's name for use in a query.
 *
 * Textpattern can be installed to a shared database, this is
 * achieved by prefixing database tables. This function
 * can be used to add those prefixes to a known
 * named table when building SQL statements.
 *
 * Always use this function, or the safe_pfx_j(), when you
 * refer tables in raw SQL statements, including where
 * clauses, joins and sub-queries.
 *
 * This function will also quote the table name if necessary.
 *
 * You don't need to use this function in any of the dedicated
 * "table" parameters database functions offer. Any table used
 * in a table parameter is prefixed for you.
 *
 * @param  string $table The database table
 * @return string The $table with a prefix
 * @uses   PFX
 * @see    safe_pfx_j()
 * @example
 * if (safe_query('DROP TABLE '.safe_pfx('myTable'))
 * {
 * 	echo 'myTable dropped';
 * }
 */

	function safe_pfx($table)
	{
		$name = PFX.$table;
		if (preg_match('@[^\w._$]@', $name))
		{
			return '`'.$name.'`';
		}
		return $name;
	}

/**
 * Prefixes a database table's name for use in a joined query.
 *
 * This function prefixes the given table name similarly to
 * safe_pfx(), but also creates a named, unprefixed, AS alias for it.
 *
 * The created alias is same as the table name given. This function
 * is here to help to make joined queries where you need to refer to two
 * or more tables in a one query.
 *
 * As with safe_pfx(), you don't need to use this function in any of the dedicated
 * "table" parameters database functions offer. Any table used in a table parameter
 * is prefixed for you.
 *
 * @param  string $table The database table, or comma-separated list of tables
 * @return string The $table with a prefix
 * @uses   PFX
 * @see    safe_pfx()
 * @example
 * if ($r = getRows('SELECT id FROM '.safe_pfx_j('tableA').' JOIN '.safe_pfx('tableB').' ON tableB.id = tableA.id and tableB.active = 1'))
 * {
 * 	print_r($r);
 * }
 */

	function safe_pfx_j($table)
	{
		$ts = array();
		foreach (explode(',', $table) as $t)
		{
			$name = PFX.trim($t);
			if (preg_match('@[^\w._$]@', $name))
			{
				$ts[] = "`$name`".(PFX ? " as `$t`" : '');
			}
			else
			{
				$ts[] = "$name".(PFX ? " as $t" : '');
			}
		}
		return join(', ', $ts);
	}

/**
 * Escapes special characters in a string for use in an SQL statement.
 *
 * @param  string $in The input string
 * @return string
 * @since  4.5.0
 * @see    doSlash()
 * @example
 * if (safe_update('myTable', "value='".doSlash($user_value)."'", "name='".doSlash($user_name)."'"))
 * {
 * 	echo 'Updated.';
 * }
 */

	function safe_escape($in = '')
	{
		global $DB;
		return mysql_real_escape_string($in, $DB->link);
	}

/**
 * Executes an SQL statement.
 *
 * @param  string $q     The SQL statement to execute
 * @param  bool   $debug Dump query
 * @param  bool   $unbuf If TRUE, executes the statement without fetching and buffering the results
 * @return mixed
 * @example
 * echo safe_query('SELECT * FROM table');
 */

	function safe_query($q = '', $debug = false, $unbuf = false)
	{
		global $DB, $txpcfg, $qcount, $qtime, $production_status;
		$method = (!$unbuf) ? 'mysql_query' : 'mysql_unbuffered_query';
		if (!$q)
		{
			return false;
		}
		if ($debug or TXP_DEBUG === 1)
		{
			dmp($q);
		}

		$start = getmicrotime();
		$result = $method($q,$DB->link);
		$time = getmicrotime() - $start;
		@$qtime += $time;
		@$qcount++;
		if ($result === false)
		{
			trigger_error(mysql_error(), E_USER_ERROR);
		}

		trace_add("[SQL ($time): $q]");

		if(!$result)
		{
			return false;
		}
		return $result;
	}

/**
 * Deletes a row from a table.
 *
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return bool   FALSE on error
 * @see    safe_update()
 * @see    safe_insert()
 * @example
 * if (safe_delete('myTable', "name='test'"))
 * {
 * 	echo "'test' removed from 'myTable'.";
 * }
 */

	function safe_delete($table, $where, $debug = false)
	{
		$q = "delete from ".safe_pfx($table)." where $where";
		if ($r = safe_query($q, $debug))
		{
			return true;
		}
		return false;
	}

/**
 * Updates a table row.
 *
 * @param  string $table The table
 * @param  string $set   The set clause
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return bool   FALSE on error
 * @see    safe_insert()
 * @see    safe_delete()
 * @example
 * if (safe_update('myTable', "myField='newValue'", "name='test'"))
 * {
 * 	echo "'test' updated, 'myField' set to 'newValue'";
 * }
 */

	function safe_update($table, $set, $where, $debug = false)
	{
		$q = "update ".safe_pfx($table)." set $set where $where";
		if ($r = safe_query($q, $debug))
		{
			return true;
		}
		return false;
	}

/**
 * Inserts a new row into a table.
 *
 * @param  string   $table The table
 * @param  string   $set   The set clause
 * @param  bool     $debug Dump query
 * @return int|bool The last generated ID or FALSE on error. If the ID is 0, returns TRUE
 * @see    safe_update()
 * @see    safe_delete()
 * @example
 * if ($id = safe_insert('myTable', "name='test', myField='newValue'"))
 * {
 * 	echo "Created a row to 'myTable' with the name 'test'. It has ID of {$id}.";
 * }
 */

	function safe_insert($table, $set, $debug = false)
	{
		global $DB;
		$q = "insert into ".safe_pfx($table)." set $set";
		if ($r = safe_query($q, $debug))
		{
			$id = mysql_insert_id($DB->link);
			return ($id === 0 ? true : $id);
		}
		return false;
	}

/**
 * Inserts a new row, or updates an existing if a matching row is found.
 *
 * @param  string   $table The table
 * @param  string   $set   The set clause
 * @param  string   $where The where clause
 * @param  bool     $debug Dump query
 * @return int|bool The last generated ID or FALSE on error. If the ID is 0, returns TRUE
 * @example
 * if ($r = safe_upsert('myTable', "data='foobar'", "name='example'"))
 * {
 * 	echo "Inserted new row to 'myTable', or updated 'example'.";
 * }
 */

	function safe_upsert($table, $set, $where, $debug = false)
	{
		// FIXME: lock the table so this is atomic?
		$r = safe_update($table, $set, $where, $debug);
		if ($r and (mysql_affected_rows() or safe_count($table, $where, $debug)))
		{
			return $r;
		}
		else
		{
			return safe_insert($table, join(', ', array($where, $set)), $debug);
		}
	}

/**
 * Changes the structure of a table.
 *
 * @param   string $table The table
 * @param   string $alter The statement to execute
 * @param   bool   $debug Dump query
 * @return  bool   FALSE on error
 * @example 
 * if (safe_alter('myTable', 'ADD myColumn TINYINT(1)'))
 * {
 * 	echo "'myColumn' added to 'myTable'";
 * }
 */

	function safe_alter($table, $alter, $debug = false)
	{
		$q = "alter table ".safe_pfx($table)." $alter";
		if ($r = safe_query($q, $debug))
		{
			return true;
		}
		return false;
	}

/**
 * Optimises a table.
 *
 * @param  string $table The table
 * @param  bool   $debug Dump query
 * @return bool   FALSE on error
 * @example
 * if (safe_optimize('myTable'))
 * {
 * 	echo "myTable optimised successfully.";
 * }
 */

	function safe_optimize($table, $debug = false)
	{
		$q = "optimize table ".safe_pfx($table)."";
		if ($r = safe_query($q, $debug))
		{
			return true;
		}
		return false;
	}

/**
 * Repairs a table.
 *
 * @param  string $table The table
 * @param  bool   $debug Dump query
 * @return bool
 * @example
 * if (safe_repair('myTable'))
 * {
 * 	echo "myTable repaired successfully.";
 * }
 */

	function safe_repair($table, $debug = false)
	{
		$q = "repair table ".safe_pfx($table)."";
		if ($r = safe_query($q, $debug))
		{
			return true;
		}
		return false;
	}

/**
 * Gets a field from a row.
 *
 * If the query results in multiple matches, the first
 * row returned is used.
 *
 * @param  string $thing The field
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return mixed  The field or FALSE on error
 * @example
 * if ($field = safe_field('column', 'table', '1=1'))
 * {
 * 	echo $field;
 * }
 */

	function safe_field($thing, $table, $where, $debug = false)
	{
		$q = "select $thing from ".safe_pfx_j($table)." where $where";
		$r = safe_query($q,$debug);
		if (@mysql_num_rows($r) > 0)
		{
			$f = mysql_result($r, 0);
			mysql_free_result($r);
			return $f;
		}
		return false;
	}

/**
 * Gets a list of values from a table's column.
 *
 * @param  string $thing The column
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return array
 */

	function safe_column($thing, $table, $where, $debug = false)
	{
		$q = "select $thing from ".safe_pfx_j($table)." where $where";
		$rs = getRows($q,$debug);
		if ($rs)
		{
			foreach ($rs as $a)
			{
				$v = array_shift($a);
				$out[$v] = $v;
			}
			return $out;
		}
		return array();
	}

/**
 * Fetch a column as an numeric array.
 *
 * @param  string $thing The field
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return array  Numeric array of column values
 * @since  4.5.0
 */

	function safe_column_num($thing, $table, $where, $debug = false)
	{
		$q = "select $thing from ".safe_pfx_j($table)." where $where";
		$rs = getRows($q,$debug);
		if ($rs)
		{
			foreach ($rs as $a)
			{
				$v = array_shift($a);
				$out[] = $v;
			}
			return $out;
		};
		return array();
	}

/**
 * Gets a row from a table as an associative array.
 *
 * @param  string $things The select clause
 * @param  string $table  The table
 * @param  string $where  The where clause
 * @param  bool   $debug  Dump query
 * @return array
 * @see    safe_rows()
 * @see    safe_rows_start()
 * @uses   getRow()
 * @example
 * if ($row = safe_row('column', 'table', '1=1'))
 * {
 * 	echo $row['column'];
 * }
 */

	function safe_row($things, $table, $where, $debug = false)
	{
		$q = "select $things from ".safe_pfx_j($table)." where $where";
		$rs = getRow($q,$debug);
		if ($rs)
		{
			return $rs;
		}
		return array();
	}

/**
 * Gets a list rows from a table as an associative array.
 *
 * When working with large result sets remember that this function, 
 * unlike safe_rows_start(), loads results to memory all at once.
 * To optimise performance in such situations, use safe_rows_start()
 * instead.
 *
 * @param  string $things The select clause
 * @param  string $table  The table
 * @param  string $where  The where clause
 * @param  bool   $debug  Dump query
 * @return array  Returns an empty array if no mathes are found
 * @see    safe_row()
 * @see    safe_rows_start()
 * @uses   getRows()
 * @example
 * $rs = safe_rows('column', 'table', '1=1');
 * foreach ($rs as $row)
 * {
 * 	echo $row['column'];
 * }
 */

	function safe_rows($things, $table, $where, $debug = false)
	{
		$q = "select $things from ".safe_pfx_j($table)." where $where";
		$rs = getRows($q,$debug);
		if ($rs)
		{
			return $rs;
		}
		return array();
	}

/**
 * Selects rows from a table and returns result as a resource.
 *
 * @param  string        $things The select clause
 * @param  string        $table  The table
 * @param  string        $where  The where clause
 * @param  bool          $debug  Dump query
 * @return resource|bool A result resouce or FALSE on error
 * @see    nextRow()
 * @see    numRows()
 * @example
 * if ($rs = safe_rows_start('column', 'table', '1=1'))
 * {
 * 	while ($row = nextRow($rs))
 * 	{
 * 		echo $row['column'];
 * 	}
 * }
 */

	function safe_rows_start($things, $table, $where, $debug = false)
	{
		$q = "select $things from ".safe_pfx_j($table)." where $where";
		return startRows($q,$debug);
	}

/**
 * Counts number of rows in a table.
 *
 * @param  string   $table The table
 * @param  string   $where The where clause
 * @param  bool     $debug Dump query
 * @return int|bool Number of rows or FALSE on error
 * @example
 * if (($count = safe_count('myTable', '1=1')) !== false)
 * {
 * 	echo "myTable contains {$count} rows.";
 * }
 */

	function safe_count($table, $where, $debug = false)
	{
		return getCount($table, $where, $debug);
	}

/**
 * Shows information about a table.
 *
 * @param  string   $thing The information to show, e.g. "index", "columns"
 * @param  string   $table The table
 * @param  bool     $debug Dump query
 * @return array
 * @example
 * print_r(safe_show('columns', 'myTable'));
 */

	function safe_show($thing, $table, $debug = false)
	{
		$q = "show $thing from ".safe_pfx($table)."";
		$rs = getRows($q,$debug);
		if ($rs)
		{
			return $rs;
		}
		return array();
	}

/**
 * Gets a field from a row.
 *
 * This function offers an alternative short-hand syntax to
 * safe_field(). Most notably, this internally manages
 * value escaping.
 *
 * @param  string $col   The field to get
 * @param  string $table The table
 * @param  string $key   The searched field
 * @param  string $val   The searched value
 * @param  bool   $debug Dump query
 * @return mixed  The field or FALSE on error
 * @see    safe_field()
 * @example
 * echo fetch('name', 'myTable', 'id', 12);
 */

	function fetch($col, $table, $key, $val, $debug = false)
	{
		$key = doSlash($key);
		$val = (is_int($val)) ? $val : "'".doSlash($val)."'";
		$q = "select $col from ".safe_pfx($table)." where `$key` = $val limit 1";
		if ($r = safe_query($q, $debug))
		{
			$thing = (mysql_num_rows($r) > 0) ? mysql_result($r,0) : '';
			mysql_free_result($r);
			return $thing;
		}
		return false;
	}

/**
 * Gets a row as an associative array.
 *
 * @param  string     $query The SQL statement to execute
 * @param  bool       $debug Dump query
 * @return array|bool The row's values or FALSE on error
 * @see    safe_row()
 */

	function getRow($query, $debug = false)
	{
		if ($r = safe_query($query,$debug))
		{
			$row = (mysql_num_rows($r) > 0) ? mysql_fetch_assoc($r) : false;
			mysql_free_result($r);
			return $row;
		}
		return false;
	}

/**
 * Gets multiple rows as an associative array.
 *
 * If you need to run simple SELECT queries
 * that select rows from a table, please see
 * safe_rows() and safe_rows_start() first.
 *
 * @param  string     $query The SQL statement to execute
 * @param  bool       $debug Dump query
 * @return array|bool The rows or FALSE on error
 * @see    safe_rows()
 * @example
 * if ($rs = getRows('SELECT * FROM table'))
 * {
 * 	print_r($rs);
 * }
 */

	function getRows($query, $debug = false)
	{
		if ($r = safe_query($query, $debug))
		{
			if (mysql_num_rows($r) > 0)
			{
				while ($a = mysql_fetch_assoc($r))
				{
					$out[] = $a;
				}
				mysql_free_result($r);
				return $out;
			}
		}
		return false;
	}

/**
 * Executes an SQL statement and returns results.
 *
 * This function is indentical to safe_query() apart
 * from the missing $unbuf argument.
 *
 * @param  string $query The SQL statement to execute
 * @param  bool   $debug Dump query
 * @return mixed
 * @see    safe_query()
 * @access private
 */

	function startRows($query, $debug = false)
	{
		return safe_query($query, $debug);
	}

/**
 * Gets a next row as an associative array from a result resource.
 *
 * The function will free up memory reserved by the result resource
 * if called after the last row.
 *
 * @param   resource    $r The result resource
 * @return  array|bool  The row, or FALSE if there are no more rows
 * @see     safe_rows_start()
 * @example
 * if ($rs = safe_rows_start('column', 'table', '1=1'))
 * {
 * 	while ($row = nextRow($rs))
 * 	{
 * 		echo $row['column'];
 * 	}
 * }
 */

	function nextRow($r)
	{
		$row = mysql_fetch_assoc($r);
		if ($row === false)
		{
			mysql_free_result($r);
		}
		return $row;
	}

/**
 * Gets the number of rows in a result resource.
 *
 * @param  resource $r The result resource
 * @return int|bool The number of rows or FALSE on error
 * @see    safe_rows_start()
 * @example
 * if ($rs = safe_rows_start('column', 'table', '1=1'))
 * {
 * 	echo numRows($rs);
 * }
 */

	function numRows($r)
	{
		return mysql_num_rows($r);
	}

/**
 * Gets the contents of a single cell from a resource set.
 *
 * @param  string      $query The SQL statement to execute
 * @param  bool        $debug Dump query
 * @return string|bool The contents, empty if no results were found or FALSE on error
 */

	function getThing($query, $debug = false)
	{
		if ($r = safe_query($query, $debug))
		{
			$thing = (mysql_num_rows($r) != 0) ? mysql_result($r,0) : '';
			mysql_free_result($r);
			return $thing;
		}
		return false;
	}

/**
 * Return values of one column from multiple rows in a num indexed array.
 *
 * @param  string $query The SQL statement to execute
 * @param  bool   $debug Dump query
 * @return array
 */

	function getThings($query, $debug = false)
	{
		$rs = getRows($query, $debug);
		if ($rs)
		{
			foreach ($rs as $a)
			{
				$out[] = array_shift($a);
			}
			return $out;
		}
		return array();
	}

/**
 * Counts number of rows in a table.
 *
 * This function is identical to safe_count().
 *
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @access private
 * @see    safe_count()
 */

	function getCount($table, $where, $debug = false)
	{
		return getThing("select count(*) from ".safe_pfx_j($table)." where $where",$debug);
	}

/**
 * Gets a tree structure.
 *
 * This function is used by categories.
 *
 * @param  string $root  The root
 * @param  string $type  The type
 * @param  string $where The where clause
 * @param  string $tbl   The table
 * @return array
 */

	function getTree($root, $type, $where = '1=1', $tbl = 'txp_category')
	{

		$root = doSlash($root);
		$type = doSlash($type);

		$rs = safe_row(
			"lft as l, rgt as r",
			$tbl,
			"name='$root' and type = '$type'"
		);

		if (!$rs)
		{
			return array();
		}
		extract($rs);

		$out = array();
		$right = array();

		$rs = safe_rows_start(
			"id, name, lft, rgt, parent, title",
			$tbl,
			"lft between $l and $r and type = '$type' and name != 'root' and $where order by lft asc"
		);

		while ($rs and $row = nextRow($rs))
		{
			extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt)
			{
				array_pop($right);
			}

			$out[] =
				array(
					'id' => $id,
					'name' => $name,
					'title' => $title,
					'level' => count($right),
					'children' => ($rgt - $lft - 1) / 2,
					'parent' => $parent
				);

			$right[] = $rgt;
		}
		return $out;
	}

/**
 * Gets a tree path up to a target.
 *
 * This function is used by categories.
 *
 * @param  string $target The target
 * @param  string $type   The category type
 * @param  string $tbl    The table
 * @return array
 */

	function getTreePath($target, $type, $tbl = 'txp_category')
	{

		$rs = safe_row(
			"lft as l, rgt as r",
			$tbl,
			"name='".doSlash($target)."' and type = '".doSlash($type)."'"
		);
		if (!$rs)
		{
			return array();
		}
		extract($rs);

		$rs = safe_rows_start(
			"*",
			$tbl,
				"lft <= $l and rgt >= $r and type = '".doSlash($type)."' order by lft asc"
		);

		$out = array();
		$right = array();

		while ($rs and $row = nextRow($rs))
		{
			extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt)
			{
				array_pop($right);
			}

			$out[] =
				array(
					'id' => $id,
					'name' => $name,
					'title' => $title,
					'level' => count($right),
					'children' => ($rgt - $lft - 1) / 2
				);

			$right[] = $rgt;
		}
		return $out;
	}

/**
 * Rebuilds a nested tree set.
 *
 * This function is used by categories.
 *
 * @param  string $parent The parent
 * @param  string $left   The left ID
 * @param  string $type   The category type
 * @param  string $tbl    The table
 * @return int    The next left ID
 */

	function rebuild_tree($parent, $left, $type, $tbl = 'txp_category')
	{
		$left  = assert_int($left);
		$right = $left+1;

		$parent = doSlash($parent);
		$type   = doSlash($type);

		$result = safe_column("name", $tbl,
			"parent='$parent' and type='$type' order by name");

		foreach ($result as $row)
		{
			$right = rebuild_tree($row, $right, $type, $tbl);
		}

		safe_update(
			$tbl,
			"lft=$left, rgt=$right",
			"name='$parent' and type='$type'"
		);
		return $right+1;
	}

/**
 * Rebuilds a tree.
 *
 * This function is used by categories.
 *
 * @param  string $type   The category type
 * @param  string $tbl    The table
 * @return int    The next left ID
 */

	function rebuild_tree_full($type, $tbl = 'txp_category')
	{
		# fix circular references, otherwise rebuild_tree() could get stuck in a loop
		safe_update($tbl, "parent=''", "type='".doSlash($type)."' and name='root'");
		safe_update($tbl, "parent='root'", "type='".doSlash($type)."' and parent=name");

		rebuild_tree('root', 1, $type, $tbl);
	}

/**
 * Gets all preferences as an array.
 *
 * Returns all preference values from the database as an array.
 * This function shouldn't be used to retrieve selected preferences,
 * see get_pref() instead.
 *
 * If run on an authenticated admin page, the results include current user's
 * private preferences. Any global preference overrides equally named user prefs.
 *
 * @return array
 * @access private
 * @see    get_pref()
 */

	function get_prefs()
	{
		global $txp_user;
		$out = array();

		// Get current user's private prefs.
		if ($txp_user)
		{
			$r = safe_rows_start('name, val', 'txp_prefs', 'prefs_id=1 AND user_name=\''.doSlash($txp_user).'\'');
			if ($r)
			{
				while ($a = nextRow($r))
				{
					$out[$a['name']] = $a['val'];
				}
			}
		}

		// Get global prefs, eventually override equally named user prefs.
		$r = safe_rows_start('name, val', 'txp_prefs', 'prefs_id=1 AND user_name=\'\'');
		if ($r)
		{
			while ($a = nextRow($r))
			{
				$out[$a['name']] = $a['val'];
			}
		}
		return $out;
	}

/**
 * Returns an error page.
 *
 * This function is used to return a bailout page when resolving database connections fails.
 * Sends a HTTP 503 error status and displays the last logged MySQL error message.
 *
 * @return string HTML HTML5 document
 * @access private
 */

	function db_down()
	{
		// 503 status might discourage search engines from indexing or caching the error message.
		txp_status_header('503 Service Unavailable');
		$error = mysql_error();
		return <<<eod
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Database unavailable</title>
</head>
<body>
	<p>Database unavailable.</p>
	<!-- $error -->
</body>
</html>
eod;
	}

?>

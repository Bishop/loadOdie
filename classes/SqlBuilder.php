<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandr
 * Date: 29.09.10
 */
 
class SqlBuilder {
	protected $tables = array();
	protected $active_table = '';
	protected $limit = '';
	protected $joins = array();

	public static function newQuery() {
		return new self();
	}

	public function from($table) {
		$this->active_table = $table;
		if (!array_key_exists($table, $this->tables)) {
			$this->tables[$table] = array(
				'select' => array(),
				'where' => array(),
				'order' => array(),
			);
		}
		return $this;
	}

	public function select() {
		$this->tables[$this->active_table]['select'] = array_merge($this->tables[$this->active_table]['select'], func_get_args());
		return $this;
	}

	public function where($field, $value) {
		$this->tables[$this->active_table]['where'][$field] = $value;
		return $this;
	}

	public function order($field) {
		$this->tables[$this->active_table]['order'][] = $field;
		return $this;
	}

	public function limit($limit, $offset = 0) {
		$this->limit = "$offset, $limit";
		return $this;
	}

	public function join($refs, $table, $field) {
		$this->joins[$this->active_table][] = compact('refs', 'table', 'field');
		return $this;
	}

	public function getSql($countOnly = false) {
		$select = array();
		$where = array();
		$order = array();
		foreach ($this->tables as $table => $data) {
			foreach ($data['select'] as $s) {
				$select[] = "`$table`." . ($s == '*' ? $s : "`$s`");
			}
			foreach ($data['where'] as $field => $value) {
				$where[] = "`$table`.`$field` = $value";
			}
			foreach ($data['order'] as $o) {
				$order[] = "`$table`." . preg_replace('!(.+?)( (?:A|DE)SC)?$!', '`$1`$2', $o);
			}
		}

		$sql = 'SELECT ' . ($countOnly ? 'COUNT(*)' : implode(', ', $select));
		$from = array_combine(array_keys($this->tables), array_map(create_function('$a', 'return "`$a`";'), array_keys($this->tables)));

		foreach ($this->joins as $table => $table_data) {
			foreach ($table_data as $data) {
				$from[$data['table']] = "JOIN `{$data['table']}` ON `$table`.`{$data['refs']}` = `{$data['table']}`.`{$data['field']}`";
			}
		}

		$sql .= ' FROM ' . implode(' ', array_values($from));
		empty($where) or $sql .= ' WHERE ' . implode(' AND ', $where);
		empty($order) or $sql .= ' ORDER BY ' . implode(', ', $order);
		empty($this->limit) or $sql .= ' LIMIT ' . $this->limit;

		return $sql;
	}
}
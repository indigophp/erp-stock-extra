<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Indigo\Erp\Stock;

use Orm\Model;

class HasOne extends \Orm\HasOne
{
	public function join($alias_from, $rel_name, $alias_to_nr, $conditions = array())
	{
		$alias_to = 't'.$alias_to_nr;
		$model = array(
			'model'        => $this->model_to,
			'connection'   => call_user_func(array($this->model_to, 'connection')),
			'table'        => array(call_user_func(array($this->model_to, 'table')), $alias_to),
			'primary_key'  => call_user_func(array($this->model_to, 'primary_key')),
			'join_type'    => \Arr::get($conditions, 'join_type') ?: \Arr::get($this->conditions, 'join_type', 'left'),
			'join_on'      => array(),
			'columns'      => $this->select($alias_to),
			'rel_name'     => strpos($rel_name, '.') ? substr($rel_name, strrpos($rel_name, '.') + 1) : $rel_name,
			'relation'     => $this,
			'where'        => \Arr::get($conditions, 'where', array()),
			'order_by'     => \Arr::get($conditions, 'order_by') ?: \Arr::get($this->conditions, 'order_by', array()),
		);
var_dump($model); exit;
		reset($this->key_to);
		foreach ($this->key_from as $key)
		{
			$key_to = current($this->key_to);
			$alias = array();

			if ($key instanceof \Fuel\Core\Database_Query_Builder_Select)
			{
				$key = $key->compile();
				$key = \Str::tr($key, array('alias_to' => $alias_to, 'alias_from' => $alias_from, $rel_name => $alias_to));
				$key = \DB::expr('(' . $key . ')');
				$alias[0] = $key;
			}
			else
			{
				$alias[0] = $alias_from . '.' . $key;
			}

			$alias[1] = '=';

			if ($key_to instanceof \Fuel\Core\Database_Query_Builder_Select)
			{
				$key_to = current($this->key_to);
				$key_to = $key_to->compile();
				$key_to = \Str::tr($key_to, array('alias_to' => $alias_to, 'alias_from' => $alias_from, $rel_name => $alias_to));
				$key_to = \DB::expr('(' . $key_to . ')');
				$alias[2] = $key_to;
			}
			else
			{
				$alias[2] = $alias_to . '.' . $key_to;
			}

			$model['join_on'][] = $alias;
			next($this->key_to);
		}

		foreach (array(\Arr::get($this->conditions, 'where', array()), \Arr::get($conditions, 'join_on', array())) as $c)
		{
			foreach ($c as $key => $condition)
			{
				! is_array($condition) and $condition = array($key, '=', $condition);
				if ( ! $condition[0] instanceof \Fuel\Core\Database_Expression and strpos($condition[0], '.') === false)
				{
					$condition[0] = $alias_to.'.'.$condition[0];
				}
				is_string($condition[2]) and $condition[2] = \Db::quote($condition[2], $model['connection']);

				$model['join_on'][] = $condition;
			}
		}

		return array($rel_name => $model);
	}
}

<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Session\Bag;

/**
 * Class AbstractSessionBag
 *
 * @since 1.0
 */
class SessionBag implements SessionBagInterface
{
	/**
	 * Property data.
	 *
	 * @var  array
	 */
	protected $data = array();

	/**
	 * setData
	 *
	 * @param array $data
	 *
	 * @return  void
	 */
	public function setData(array &$data)
	{
		$this->data = &$data;
	}

	/**
	 * get
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return  mixed
	 */
	public function get($key, $default)
	{
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}

	/**
	 * set
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return  $this
	 */
	public function set($key, $value)
	{
		if ($value === null)
		{
			if (isset($this->data[$key]))
			{
				unset($this->data[$key]);
			}
		}
		else
		{
			$this->data[$key] = $value;
		}

		return $this;
	}

	/**
	 * has
	 *
	 * @param string $name
	 *
	 * @return  bool
	 */
	public function has($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * all
	 *
	 * @return  array
	 */
	public function all()
	{
		return $this->data;
	}

	/**
	 * clean
	 *
	 * @return  $this
	 */
	public function clean()
	{
		$this->data = array();

		return $this;
	}
}
 
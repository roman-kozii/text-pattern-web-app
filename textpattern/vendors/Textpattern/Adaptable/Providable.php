<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Adapting providable base class.
 *
 * <code>
 * class MyProvidableAdaptee extends Textpattern_Adaptable_Providable
 * {
 * 	public function getDefaultAdaptableProvider()
 * 	{
 * 		return new MyAdapterDriver();
 * 	}
 * }
 * </code>
 *
 * @since   4.6.0
 * @package Adaptable
 */

abstract class Textpattern_Adaptable_Providable implements Textpattern_Adaptable_ProvidableInterface
{
	/**
	 * Stores an instance of the current provider.
	 *
	 * @var Textpattern_Adaptable_Adapter
	 */

	private $adapter;

	/**
	 * Whether its the first run.
	 *
	 * @var bool
	 */

	private $firstRun = true;

	/**
	 * {@inheritdoc}
	 */

	public function setAdapter(Textpattern_Adaptable_Adapter $adapter)
	{
		$this->adapter = $adapter;
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */

	public function getAdapter()
	{
		if (!$this->adapter)
		{
			$this->adapter = $this->getDefaultAdapter();
		}

		if ($this->firstRun)
		{
			$this->firstRun = false;
			$this->adapter->providable = $this;
		}

		return $this->adapter;
	}

	/**
	 * Redirects method calls to the adapter.
	 *
	 * @param  string $name The method
	 * @param  array  $args The arguments
	 * @return mixed
	 */

	public function __call($name, array $args)
	{
		return call_user_func_array(array($this->getAdapter(), $name), $args);
	}
}

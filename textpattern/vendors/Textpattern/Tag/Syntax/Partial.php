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
 * Template partials tags.
 *
 * @since  4.6.0
 */

class Textpattern_Tag_Syntax_Partial
{
	/**
	 * Conditional for yield.
	 *
	 * @param  array  $atts
	 * @param  string $thing
	 * @return string
	 */

	static public function if_yield($atts, $thing)
	{
		global $yield;

		extract(lAtts(array(
			'value' => null,
		), $atts));

		$inner = end($yield);

		return parse(EvalElse($thing, $inner !== null && ($value === null || $inner == $value)));
	}
}
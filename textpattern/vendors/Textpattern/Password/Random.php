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
 * Generates random bytes.
 *
 * Could be used as a seed or as a unique value itself.
 * Note that the value is not intended to be human-readable,
 * and as such should not be used for passwords. To generate
 * passwords, see Textpattern_Password_Generator instead.
 *
 * <code>
 * echo Txp::get('PasswordRandom')->generate(196);
 * </code>
 *
 * @since   4.6.0
 * @package Password
 * @see     Textpattern_Password_Generator
 */

class Textpattern_Password_Random extends Textpattern_Password_Generator
{
	/**
	 * {@inheritdoc}
	 */

	public function getCharacterTable()
	{
		return array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!', '@', '#', '$', '%', '&', '*', '?');
	}

	/**
	 * Generates random bytes as a string of given length.
	 *
	 * <code>
	 * echo Txp::get('PasswordRandom')->generate(196);
	 * </code>
	 *
	 * @param  int    $length The length of the generated value, minimum 32
	 * @return string The value
	 */

	public function generate($length)
	{
		$length = max(32, (int) $length);
		$bytes = $length / 2;

		if (function_exists('mcrypt_create_iv') && version_compare(PHP_VERSION, '5.3.0') >= 0)
		{
			$random = mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);

			if ($random && strlen($random) === $bytes)
			{
				return bin2hex($random);
			}
		}

		if (IS_WIN === false && file_exists('/dev/urandom') && is_readable('/dev/urandom') && ($fp = fopen('/dev/urandom', 'rb')) !== false)
		{
			if (function_exists('stream_set_read_buffer'))
			{
				stream_set_read_buffer($fp, 0);
			}

			$random = fread($fp, $bytes);
			fclose($fp);

			if ($random && strlen($random) === $bytes)
			{
				return bin2hex($random);
			}
		}

		if (IS_WIN === false && function_exists('openssl_random_pseudo_bytes') && version_compare(PHP_VERSION, '5.3.4') >= 0)
		{
			$random = openssl_random_pseudo_bytes($bytes, $strong);

			if ($random && $strong === true && strlen($random) === $bytes)
			{
				return bin2hex($random);
			}
		}

		return parent::generate($length);
	}
}

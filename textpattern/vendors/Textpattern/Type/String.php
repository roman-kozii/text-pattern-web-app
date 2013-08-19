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
 * String object.
 *
 * Wraps around Multibyte string extension,
 * offering multi-byte safe string functions.
 *
 * @since   4.6.0
 * @package Type
 * @example
 * $string = new Textpattern_Type_String(' Hello World! ');
 * echo (string) $string->trim()->replace('!', '.')->lower();
 */

class Textpattern_Type_String implements Textpattern_Type_Template
{
	/**
	 * The string.
	 *
	 * @var string
	 */

	protected $string;

	/**
	 * Whether multibyte string extension is available.
	 *
	 * @var bool
	 */

	static protected $mbString = null;

	/**
	 * Whether encoding functions are available.
	 *
	 * @var bool
	 */

	static protected $encode = null;

	/**
	 * Expected encoding.
	 *
	 * @var string
	 */

	protected $encoding = 'UTF-8';

	/**
	 * Constructor.
	 *
	 * @param string $string The string
	 */

	public function __construct($string)
	{
		$this->string = (string) $string;

		if (self::$mbString === null)
		{
			self::$mbString = function_exists('mb_strlen');
		}

		if (self::$encode === null)
		{
			self::$encode = function_exists('utf8_decode');
		}
	}

	/**
	 * Gets the string.
	 *
	 * @return string
	 * @see    Textpattern_Type_String::getString()
	 * @example
	 * echo (string) new Textpattern_Type_String('Hello World!');
	 */

	public function __toString()
	{
		return (string) $this->string;
	}

	/**
	 * Gets the string.
	 *
	 * @return string
	 * @see    Textpattern_Type_String::_toString()
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo $string->getString();
	 */

	public function getString()
	{
		return (string) $this->string;
	}

	/**
	 * Gets string length.
	 *
	 * @return int
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo $string->getLength();
	 */

	public function getLength()
	{
		if (self::$mbString)
		{
			return mb_strlen($this->string, $this->encoding);
		}

		if (self::$encode)
		{
			return strlen(utf8_decode($this->string));
		}

		return strlen($this->string);
	}

	/**
	 * Finds the first occurrence of a string in the string.
	 *
	 * @param  string   $needle The string to find
	 * @param  int      $offset The search offset
	 * @return int|bool FALSE if the string does not contain results
	 * @example
	 * $string = new Textpattern_Type_String('#@language');
	 * echo $string->position('@');
	 */

	public function position($needle, $offset = 0)
	{
		if (self::$mbString)
		{
			return mb_strpos($this->string, $needle, $offset, $this->encoding);
		}

		return strpos($this->string, $needle, $offset);
	}

	/**
	 * Gets substring count.
	 *
	 * @param  string $needle The string to find
	 * @return int
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo $string->count('ello');
	 */

	public function count($needle)
	{
		if (self::$mbString)
		{
			return mb_substr_count($this->string, $needle, $this->encoding);
		}

		return substr_count($this->string, $needle);
	}

	/**
	 * Add slashes.
	 *
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Some "content" to slash.');
	 * echo (string) $string->addSlashes();
	 */

	public function addSlashes()
	{
		$this->string = addslashes($this->string);
		return $this;
	}

	/**
	 * HTML encodes the string.
	 *
	 * @param   int    $flags         A bitmask of one or more flags. The default is ENT_QUOTES
	 * @param   bool   $double_encode When double_encode is turned off PHP will not encode existing HTML entities, the default is to convert everything
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->html();
	 */

	public function html($flags = ENT_QUOTES, $double_encode = true)
	{
		$this->string = htmlspecialchars($this->string, $flags, $this->encoding, $double_encode);
		return $this;
	}

	/**
	 * Splits part of the string.
	 *
	 * @param  int $start  The start
	 * @param  int $length The length
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->substring(2, 5);
	 */

	public function substring($start, $length = null)
	{
		if (self::$mbString)
		{
			$this->string = mb_substr($this->string, $start, $length, $this->encoding);
		}
		else
		{
			$this->string = substr($this->string, $start, $length);
		}

		return $this;
	}

	/**
	 * Replaces all occurrences with replacements.
	 *
	 * @param  mixed $from The needle to find
	 * @param  mixed $to   The replacement
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->replace('!', '.');
	 */

	public function replace($from, $to)
	{
		$this->string = str_replace($from, $to, $this->string);
		return $this;
	}

	/**
	 * Translates substrings.
	 *
	 * @param  string $from String to find
	 * @param  string $to   The replacement
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Article <strong>{title}</strong> deleted.');
	 * echo (string) $string->tr('{title}', 'Hello {title} variable.');
	 */

	public function tr($from, $to = null)
	{
		$this->string = strtr($this->string, $from, $to);
		return $this;
	}

	/**
	 * Trims surrounding whitespace or other characters.
	 *
	 * @param  string $characters Character list
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String(' Hello World! ');
	 * echo (string) $string->trim();
	 */

	public function trim($characters = "\t\n\r\0\x0B")
	{
		$this->string = trim($this->string, $characters);
		return $this;
	}

	/**
	 * Trims whitespace or other characters from the beginning.
	 *
	 * @param  string $characters Character list
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String(' Hello World! ');
	 * echo (string) $string->ltrim();
	 */

	public function ltrim($characters = "\t\n\r\0\x0B")
	{
		$this->string = ltrim($this->string, $characters);
		return $this;
	}

	/**
	 * Trims whitespace or other characters from the end.
	 *
	 * @param  string $characters Character list
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String(' Hello World! ');
	 * echo (string) $string->rtrim();
	 */

	public function rtrim($characters = "\t\n\r\0\x0B")
	{
		$this->string = rtrim($this->string, $characters);
		return $this;
	}

	/**
	 * Splits string to chunks.
	 *
	 * @param  int    $length    The chunk length
	 * @param  string $delimiter The delimiter
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->chunk(1);
	 */

	public function chunk($length = 76, $delimiter = n)
	{
		$this->string = chunk_split($this->string, $length, $delimiter);
		return $this;
	}

	/**
	 * Word wraps the string.
	 *
	 * @param  int    $length    The line length
	 * @param  string $delimiter The line delimiter
	 * @param  bool   $cut       Cut off words
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->wordWrap();
	 */

	public function wordWrap($length = 75, $delimiter = n, $cut = false)
	{
		$this->string = wordwrap($this->string, $length, $delimiter, $cut);
		return $this;
	}

	/**
	 * Converts the string to lowercase.
	 *
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->lower();
	 */

	public function lower()
	{
		if (self::$mbString)
		{
			$this->string = mb_strtolower($this->string, $this->encoding);
		}
		else
		{
			$this->string = strtolower($this->string);
		}

		return $this;
	}

	/**
	 * Converts the string to uppercase.
	 *
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->upper();
	 */

	public function upper()
	{
		if (self::$mbString)
		{
			$this->string = mb_strtoupper($this->string, $this->encoding);
		}
		else
		{
			$this->string = strtoupper($this->string);
		}

		return $this;
	}

	/**
	 * Converts the string to titlecase.
	 *
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('hello world!');
	 * echo (string) $string->title();
	 */

	public function title()
	{
		if (self::$mbString)
		{
			$this->string = mb_convert_case($this->string, MB_CASE_TITLE, $this->encoding);
		}
		else
		{
			$this->string = ucwords($this->string);
		}

		return $this;
	}

	/**
	 * Uppercase the first letter.
	 *
	 * @return Textpattern_Type_String
	 * @example
	 * $string = new Textpattern_Type_String('Hello World!');
	 * echo (string) $string->ucfirst();
	 */

	public function ucfirst()
	{
		if (self::$mbString)
		{
			$this->string =
				mb_strtoupper(mb_substr($this->string, 0, 1, $this->encoding), $this->encoding).
				mb_substr($this->string, 1, null, $this->encoding);
		}
		else
		{
			$this->string = ucfirst($this->string);
		}

		return $this;
	}
}
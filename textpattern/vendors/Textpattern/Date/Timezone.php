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
 * Dealing with timezones.
 *
 * @package Date
 * @since   4.6.0
 */

class Textpattern_Date_Timezone
{
	/**
	 * Stores a list of details about each timezone.
	 *
	 * @var array 
	 */

	protected $details;

	/**
	 * Stores a list of timezone offsets
	 *
	 * @var array 
	 */

	protected $offsets;

	/**
	 * An array of accepted continents.
	 *
	 * @var array
	 */

	protected $continents = array(
		'Africa',
		'America',
		'Antarctica',
		'Arctic',
		'Asia',
		'Atlantic',
		'Australia',
		'Europe',
		'Indian',
		'Pacific',
	);

	/**
	 * Gets an array of safe timezones supported on this server.
	 *
	 * The following:
	 *
	 * <code>
	 * print_r(Txp::get('DateTimezone')->getTimeZones());
	 * </code>
	 *
	 * Returns:
	 *
	 * <code>
	 * Array
	 * (
	 * 	[America/New_York] => Array
	 * 	(
	 * 		[continent] => America
	 * 		[city] => New_York
	 * 		[subcity] => 
	 * 		[offset] => -18000
	 * 		[dst] => 1
	 * 	)
	 * 	[Europe/London] => Array
	 * 	(
	 * 		[continent] => Europe
	 * 		[city] => London
	 * 		[subcity] => 
	 * 		[offset] => 0
	 * 		[dst] => 1
	 * 	)
	 * )
	 * </code>
	 *
	 * Offset is the timezone offset from UTC excluding
	 * daylight saving time, DST is whether it's currently DST
	 * in the timezone. Identifiers are sorted alphabetically.
	 *
	 * @return array|bool An array of timezones, or FALSE on error
	 */

	public function getTimeZones()
	{
		if ($this->details === null)
		{
			$this->details = array();

			if (($timezones = DateTimeZone::listIdentifiers()) === false)
			{
				return false;
			}

			foreach ($timezones as $timezone)
			{
				$parts = array_pad(explode('/', $timezone), 3, '');

				if (in_array($parts[0], $this->continents, true))
				{
					try
					{
						$dateTime = new DateTime('now', new DateTimeZone($timezone));

						$data = array(
							'continent' => $parts[0],
							'city'      => $parts[1],
							'subcity'   => $parts[2],
							'offset'    => $dateTime->getOffset(),
							'dst'       => false,
						);

						if ($dateTime->format('I'))
						{
							$data['offset'] -= 3600;
							$data['dst'] = true;
						}

						$this->details[$timezone] = $data;
						$this->offsets[$data['offset']] = $timezone;
					}
					catch (Exception $e)
					{
					}
				}
			}

			ksort($this->details);
		}

		return $this->details;
	}

	/**
	 * Find a timezone identifier for the given timezone offset.
	 *
	 * More than one key might fit any given GMT offset,
	 * thus the returned value is ambiguous and merely useful for
	 * presentation purposes.
	 *
	 * @param  int         $gmtoffset
	 * @return string|bool Timezone identifier, or FALSE
	 */

	public function getOffsetIdentifier($offset)
	{
		if ($this->getTimeZones() && isset($this->offsets[$offset]))
		{
			return $this->offsets[$offset];
		}

		return false;
	}

	/**
	 * Whether DST is in effect.
	 *
	 * @param  int|null     $timestamp When
	 * @param  string|null  $timezone  Timezone identifier
	 * @return bool
	 */

	public function isDst($timestamp = null, $timezone = null)
	{
		if ($timezone !== null && !is_object($timezoone))
		{
			$timezone = new DateTimeZone($timezone);
		}

		if ($timestamp !== null)
		{
			if ((string) intval($timestamp) !== (string) $timestamp)
			{
				$timestamp = strtotime($timestamp);
			}

			$timestamp = date('Y-m-d H:m:s', $timestamp);
		}

		$dateTime = new DateTime($timestamp, $timezone);
		return (bool) $dateTime->format('I');
	}

	/**
	 * Gets the next day light saving transition period for the given timezone.
	 *
	 * Returns FALSE if the timezone does not use DST.
	 *
	 * @param 
	 * @return array|bool An array of next two transitions, or FALSE 
	 */

	public function getDstPeriod($timezone)
	{
		if ($timezone !== null && !is_object($timezone))
		{
			$timezone = new DateTimeZone($timezone);
		}

		$time = time();
		$transitions = $timezone->getTransitions();
		$start = null;
		$end = null;

		foreach ($transitions as $transition)
		{
			if ($start !== null)
			{
				$end = $transition;
				break;
			}

			if ($transition['ts'] >= $time && $transition['isdst'])
			{
				$start = $transition;
			}
		}

		if ($start)
		{
			return array($start, $end);
		}

		return false;
	}

	/**
	 * Sets the server default timezone.
	 *
	 * @param  string $identifier The timezone identifier
	 * @return Textpattern_Date_Timezone
	 */

	public function setTimeZone($identifier)
	{
		@date_default_timezone_set($identifier);
		return $this;
	}

	/**
	 * Gets the server default timezone.
	 *
	 * @return string|bool Timezone identifier
	 */

	public function getTimeZone()
	{
		return @date_default_timezone_get();
	}
}

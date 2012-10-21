<?php
/*
This is Textpattern

Copyright 2012 The Textpattern Development Team
textpattern.com
All rights reserved.

Use of this software indicates acceptance of the Textpattern license agreement

*/

/**
 * Textfilters.
 *
 * @since   4.6.0
 * @package Textfilter
 */

/**
 * Imports Textile.
 */

require_once txpath.'/lib/classTextile.php';

/**
 * Imports Validator.
 */

require_once txpath.'/lib/txplib_validator.php';

/**
 * Textfilter interface.
 *
 * This is an interface for creating text filters.
 *
 * @since   4.6.0
 * @package Textfilter
 */

interface ITextfilter
{
	/**
	 * Filters the given raw input value.
	 *
	 * @param  string $thing   The raw input string
	 * @param  array  $options Options
	 * @return string Filtered output text
	 */

	function filter($thing, $options);

	/**
	 * Gets filter-spefic help.
	 *
	 * Help can be used to set and offer HTML formatted instructions,
	 * examples and formatting tips. These instructions will be
	 * presented to the user.
	 *
	 * @return string HTML for filter-specific help
	 */

	function help();

	/**
	 * Gets a filter's globally unique identifier.
	 *
	 * @return string
	 */

	function getKey();
}

/**
 * Core textfilter implementation for a base class, plain text, nl2br, and Textile.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class Textfilter implements ITextfilter
{
	public $title, $version;
	protected $key, $options;

	/**
	 * General constructor for textfilters.
	 *
	 * @param string $key   A globally unique, persistable identifier for this particular textfilter class
	 * @param string $title The human-readable title of this filter class
	 */

	function __construct($key, $title)
	{
		global $txpversion;

		$this->key = $key;
		$this->title = $title;
		$this->version = $txpversion;
		$this->options = array(
			'lite' => false,
			'restricted' => false,
			'rel' => '',
			'noimage' => false);

		register_callback(array($this, 'register'), 'textfilter', 'register');
	}

	/**
	 * Sets filter's options.
	 *
	 * @param array $options Array of options: 'lite' => boolean, 'rel' => string, 'noimage' => boolean, 'restricted' => boolean
	 */

	private function setOptions($options)
	{
		$this->options = lAtts(array(
				'lite' => false,
				'restricted' => false,
				'rel' => '',
				'noimage' => false),
			$options);
	}

	/**
	 * Event handler, registers textfilter class with the core.
	 *
	 * @param string        $step  Not used
	 * @param string        $event Not used
	 * @param TextfilterSet $set   The set of registered textfilters
	 */

	function register($step, $event, $set)
	{
		$set[] = $this;
	}

	/**
	 * Filters the given raw input value.
	 *
	 * @param  string $thing   The raw input string
	 * @param  array  $options Options
	 * @return string Filtered output text
	 */

	function filter($thing, $options)
	{
		$this->setOptions($options);
		return $thing;
	}

	/**
	 * Get's this filter's help.
	 *
	 * @return string
	 */

	function help()
	{
		return '';
	}

	/**
	 * Gets this filter's identifier.
	 *
	 * @return string
	 */

	function getKey()
	{
		return $this->key;
	}
}

/**
 * Plain-text filter.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class PlainTextfilter extends Textfilter implements ITextfilter
{
	/**
	 * Constructor.
	 */

	function __construct()
	{
		parent::__construct(LEAVE_TEXT_UNTOUCHED, gTxt('leave_text_untouched'));
	}

	/**
	 * Filter.
	 *
	 * @param  string $thing
	 * @param  array  $options
	 * @return string
	 */

	public function filter($thing, $options)
	{
		parent::filter($thing, $options);
		return trim($thing);
	}
}

/**
 * Nl2Br filter.
 *
 * This filter converts line breaks to HTML &lt;br /&gt; tags.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class Nl2BrTextfilter extends Textfilter implements ITextfilter
{
	/**
	 * Constructor.
	 */

	function __construct()
	{
		parent::__construct(CONVERT_LINEBREAKS, gTxt('convert_linebreaks'));
	}

	/**
	 * Filter.
	 *
	 * @param string $thing
	 * @param array  $options
	 */

	public function filter($thing, $options)
	{
		parent::filter($thing, $options);
		return nl2br(trim($thing));
	}
}

/**
 * Textile filter.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class TextileTextfilter extends Textfilter implements ITextfilter
{
	/**
	 * Instance of Textile.
	 *
	 * @var Textile
	 */

	protected $textile;

	/**
	 * Constructor.
	 */

	function __construct()
	{
		parent::__construct(USE_TEXTILE, gTxt('use_textile'));

		global $prefs;
		$this->textile = new Textile($prefs['doctype']);
		$this->version = $this->textile->ver;
	}

	/**
	 * Filter.
	 *
	 * @param string $thing
	 * @param array  $options
	 */

	public function filter($thing, $options)
	{
		parent::filter($thing, $options);
		if (($this->options['restricted'])) {
			return $this->textile->TextileRestricted($thing, $this->options['lite'], $this->options['noimage'], $this->options['rel']);
		} else {
			return $this->textile->TextileThis($thing, $this->options['lite'], '', $this->options['noimage'], '', $this->options['rel']);
		}
	}

	/**
	 * Help for Textile syntax.
	 *
	 * Gives some basic Textile syntax examples,
	 * wrapped in an &lt;ul&gt;.
	 *
	 * @return string HTML
	 */

	function help()
	{
		return
			n.'<ul class="textile plain-list">'.
			n.t.'<li>'.gTxt('header').': <strong>h<em>n</em>.</strong>'.sp.
			popHelpSubtle('header', 400, 400).'</li>'.
			n.t.'<li>'.gTxt('blockquote').': <strong>bq.</strong>'.sp.
			popHelpSubtle('blockquote',400,400).'</li>'.
			n.t.'<li>'.gTxt('numeric_list').': <strong>#</strong>'.sp.
			popHelpSubtle('numeric', 400, 400).'</li>'.
			n.t.'<li>'.gTxt('bulleted_list').': <strong>*</strong>'.sp.
			popHelpSubtle('bulleted', 400, 400).'</li>'.
			n.t.'<li>'.gTxt('definition_list').': <strong>; :</strong>'.sp.
			popHelpSubtle('definition', 400, 400).'</li>'.
			n.'</ul>'.

			n.'<ul class="textile plain-list">'.
			n.t.'<li>'.'_<em>'.gTxt('emphasis').'</em>_'.sp.
			popHelpSubtle('italic', 400, 400).'</li>'.
			n.t.'<li>'.'*<strong>'.gTxt('strong').'</strong>*'.sp.
			popHelpSubtle('bold', 400, 400).'</li>'.
			n.t.'<li>'.'??<cite>'.gTxt('citation').'</cite>??'.sp.
			popHelpSubtle('cite', 500, 300).'</li>'.
			n.t.'<li>'.'-'.gTxt('deleted_text').'-'.sp.
			popHelpSubtle('delete', 400, 300).'</li>'.
			n.t.'<li>'.'+'.gTxt('inserted_text').'+'.sp.
			popHelpSubtle('insert', 400, 300).'</li>'.
			n.t.'<li>'.'^'.gTxt('superscript').'^'.sp.
			popHelpSubtle('super', 400, 300).'</li>'.
			n.t.'<li>'.'~'.gTxt('subscript').'~'.sp.
			popHelpSubtle('subscript', 400, 400).'</li>'.
			n.'</ul>'.

			n.graf(
			'"'.gTxt('linktext').'":url'.sp.popHelpSubtle('link', 400, 500)
			, ' class="textile"').

			n.graf(
			'!'.gTxt('imageurl').'!'.sp.popHelpSubtle('image', 500, 500)
			, ' class="textile"').

			n.graf(
			'<a id="textile-docs-link" href="http://textpattern.com/textile-sandbox" target="_blank">'.gTxt('More').'</a>');
	}
}

/**
 * TextfilterSet: A set of textfilters interfaces those to the core.
 *
 * @since   4.6.0
 * @package Textfilter
 * @access  private
 */

class TextfilterSet implements ArrayAccess, IteratorAggregate
{
	/**
	 * Stores an instance.
	 *
	 * @var TextfilterSet
	 */

	private static $instance;

	/**
	 * An array of filters.
	 *
	 * @var array
	 */

	private $filters;

	/**
	 * Preference name for a comma-separated list of available textfilters.
	 */

	const filterprefs = 'admin_textfilter_classes';

	/**
	 * Default textfilter preference value.
	 */

	const corefilters = 'PlainTextfilter, Nl2BrTextfilter, TextileTextfilter';

	/**
	 * Private constructor.
	 *
	 * This is not a publicly instantiable class.
	 *
	 * Creates core textfilters according to a preference and
	 * registers all available filters with the core.
	 */

	private function __construct()
	{
		// Construct core textfilters from preferences
		foreach (do_list(get_pref(self::filterprefs, self::corefilters)) as $f)	{
			if (class_exists($f)) new $f;
		}

		$this->filters = array();

		// Broadcast a request for registration to both core textfilters and textfilter plugins
		callback_event('textfilter', 'register', 0, $this);
	}

	/**
	 * Private singleton instance access
	 *
	 * @return TextfilterSet
	 */

	private static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Create an array map of filter keys vs. titles.
	 *
	 * @return array Map of 'key' => 'title' for all textfilters
	 */

	static function map()
	{
		static $out = array();
		if (empty($out)) {
			foreach (self::getInstance() as $f) {
				$out[$f->getKey()] = $f->title;
			}
		}
		return $out;
	}

	/**
	 * Filter raw input text by calling one of our known textfilters by its key.
	 *
	 * Invokes the 'textfilter'.'filter' pre- and post-callbacks.
	 *
	 * @param  string $key     The textfilter's key
	 * @param  string $thing   Raw input text
	 * @param  array  $context Filter context ('options' => array, 'field' => string, 'data' => mixed)
	 * @return string Filtered output text
	 */

	static function filter($key, $thing, $context)
	{
		// Preprocessing, anyone?
		callback_event_ref('textfilter', 'filter', 0, $thing, $context);

		$me = self::getInstance();
		if (isset($me[$key])) {
			$thing = $me[$key]->filter($thing, $context['options']);
		} else {
			// TODO: unknown filter - shall we throw an admin error?
		}

		// Postprocessing, anyone?
		callback_event_ref('textfilter', 'filter', 1, $thing, $context);

		return $thing;
	}

	/**
	 * Get help text for a certain textfilter.
	 *
	 * @param  string $key The textfilter's key
	 * @return string HTML for human-readable help
	 */

	static function help($key)
	{
		$me = self::getInstance();
		if (isset($me[$key])) {
			return $me[$key]->help();
		}
		return '';
	}

	/**
	 * ArrayAccess interface to our set of filters.
	 *
	 * @param string $key
	 * @param string $filter
	 * @see   ArrayAccess
	 */

	function offsetSet($key, $filter)
	{
		if (null === $key) $key = $filter->getKey();
		$this->filters[$key] = $filter;
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param  string $key
	 * @return string The value
	 * @see    ArrayAccess
	 */

	function offsetGet($key)
	{
		if ($this->offsetExists($key)) {
			return $this->filters[$key];
		}
		return null;
	}

	/**
	 * Whether a offset exists.
	 *
	 * @param  string $key
	 * @return bool
	 * @see    ArrayAccess
	 */

	function offsetExists($key)
	{
		return isset($this->filters[$key]);
	}

	/**
	 * Offset to unset.
	 *
	 * @param string $key
	 * @see   ArrayAccess
	 */

	function offsetUnset($key)
	{
		unset($this->filters[$key]);
	}

	/**
	 * IteratorAggregate interface.
	 *
	 * @return ArrayIterator
	 * @see    IteratorAggregate
	 */

	function getIterator()
	{
		return new ArrayIterator($this->filters);
	}
}

/**
 * Constraint for Textfilters.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class TextfilterConstraint extends Constraint
{
	/**
	 * Validates filter selection.
	 *
	 * @return bool
	 */

	function validate()
	{
		return array_key_exists($this->value, TextfilterSet::map());
	}
}

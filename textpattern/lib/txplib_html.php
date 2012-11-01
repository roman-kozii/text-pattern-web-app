<?php

/**
 * Collection of HTML widgets.
 *
 * @package HTML
 */

/**
 * @ignore
 */

	define("t","\t");
	define("n","\n");
	define("br","<br />");
	define("sp","&#160;");
	define("a","&#38;");

/**
 * Renders the admin-side footer.
 *
 * Theme's footer partial via the "admin_side" > "footer" pluggable UI
 * and send the "admin_side" > "body_end" event.
 */

	function end_page()
	{
		global $txp_user, $event, $app_mode, $theme, $textarray_script;

		if ($app_mode != 'async' && $event != 'tag')
		{
			echo '</div><!-- /txp-body --><footer role="contentinfo" class="txp-footer">';
			echo pluggable_ui('admin_side', 'footer', $theme->footer());
			callback_event('admin_side', 'body_end');
			echo n.script_js('textpattern.textarray = '.json_encode($textarray_script)).n.
			'</footer><!-- /txp-footer --></body>'.n.'</html>';
		}
	}

/**
 * Renders the user interface for one head cell of columnar data.
 *
 * @param  string $value   Element text
 * @param  string $sort    Sort criterion
 * @param  string $event   Event name
 * @param  bool   $is_link Include link to admin action in user interface according to the other params
 * @param  string $dir     Sort direction, either "asc" or "desc"
 * @param  string $crit    Search criterion
 * @param  string $method  Search method
 * @param  string $class   HTML "class" attribute applied to the resulting element
 * @param  string $step    Step name
 * @return string HTML
 */

	function column_head($value, $sort = '', $event = '', $is_link = '', $dir = '', $crit = '', $method = '', $class = '', $step = 'list')
	{
		return column_multi_head( array(
					array ('value' => $value, 'sort' => $sort, 'event' => $event, 'step' => $step, 'is_link' => $is_link,
						   'dir' => $dir, 'crit' => $crit, 'method' => $method)
				), $class);
	}

/**
 * Renders the user interface for multiple head cells of columnar data.
 *
 * @param  array  $head_items An array of hashed elements. Valid keys: 'value', 'sort', 'event', 'is_link', 'dir', 'crit', 'method'
 * @param  string $class      HTML "class" attribute applied to the resulting element
 * @return string HTML
 */

	function column_multi_head($head_items, $class = '')
	{
		$o = n.t.'<th scope="col"'.($class ? ' class="'.$class.'"' : '').'>';
		$first_item = true;
		foreach ($head_items as $item)
		{
			if (empty($item))
			{
				continue;
			}
			extract(lAtts(array(
				'value'   => '',
				'sort'    => '',
				'event'   => '',
				'step'    => 'list',
				'is_link' => '',
				'dir'     => '',
				'crit'    => '',
				'method'  => '',
			), $item));

			$o .= ($first_item) ? '' : ', '; $first_item = false;

			if ($is_link)
			{
				$o .= '<a href="index.php?step='.$step;

				$o .= ($event) ? a."event=$event" : '';
				$o .= ($sort) ? a."sort=$sort" : '';
				$o .= ($dir) ? a."dir=$dir" : '';
				$o .= ($crit != '') ? a."crit=$crit" : '';
				$o .= ($method) ? a."search_method=$method" : '';

				$o .= '">';
			}

			$o .= gTxt($value);

			if ($is_link)
			{
				$o .= '</a>';
			}
		}
		$o .= '</th>';

		return $o;
	}

/**
 * Renders a &lt;th&gt; element.
 *
 * @param  string       $text    Cell text
 * @param  string       $caption Is not used
 * @param  string|array $atts    HTML attributes
 * @return string       HTML
 */

	function hCell($text = '', $caption = '', $atts = '')
	{
		$text = ('' === $text) ? sp : $text;
		return tag($text, 'th', $atts);
	}

/**
 * Renders a link invoking an admin-side action.
 *
 * @param  string $event    Event
 * @param  string $step     Step
 * @param  string $linktext Link text
 * @param  string $class    HTML class attribute for link
 * @return string HTML
 */

	function sLink($event, $step, $linktext, $class = '')
	{
		$c = ($class) ? ' class="'.$class.'"' : '';
		return '<a href="?event='.$event.a.'step='.$step.'"'.$c.'>'.$linktext.'</a>';
	}

/**
 * Renders a link with two additional URL parameters.
 *
 * Renders a link invoking an admin-side action
 * while taking up to two additional URL parameters.
 *
 * @param  string $event    Event
 * @param  string $step     Step
 * @param  string $thing    URL parameter key #1
 * @param  string $value    URL parameter value #1
 * @param  string $linktext Link text
 * @param  string $thing2   URL parameter key #2
 * @param  string $val2     URL parameter value #2
 * @param  string $title    Anchor title
 * @return string HTML
 */

	function eLink($event, $step = '', $thing = '', $value = '', $linktext, $thing2 = '', $val2 = '', $title = 'edit')
	{
		return join('',array(
			'<a href="?event='.$event,
			($step) ? a.'step='.$step : '',
			($thing) ? a.''.$thing.'='.urlencode($value) : '',
			($thing2) ? a.''.$thing2.'='.urlencode($val2) : '',
			a.'_txp_token='.form_token(),
			'"'.(($title) ? ' title="'.gTxt($title).'"' : '') .'>'.escape_title($linktext).'</a>'
		));
	}

/**
 * Renders a link with one additional URL parameter.
 * 
 * Renders an link invoking an admin-side action while
 * taking up to one additional URL parameter.
 *
 * @param  string $event Event
 * @param  string $step  Step
 * @param  string $thing URL parameter key
 * @param  string $value URL parameter value
 * @return string HTML
 */

	function wLink($event, $step = '', $thing = '', $value = '')
	{
		return href(sp.'!'.sp, array(
			'event' => $event,
			'step' => $step,
			$thing => $value,
			'_txp_token' => form_token()
		), array('class' => 'dlink'));
	}

/**
 * Renders a delete link.
 *
 * Renders a link invoking an admin-side "delete" action
 * while taking up to two additional URL parameters.
 *
 * @param  string $event     Event
 * @param  string $step      Step
 * @param  string $thing     URL parameter key #1
 * @param  string $value     URL parameter value #1
 * @param  string $verify    Show an "Are you sure?" dialogue with this text
 * @param  string $thing2    URL parameter key #2
 * @param  string $thing2val URL parameter value #2
 * @param  bool   $get       Use GET request [false: Use POST request]
 * @param  array  $remember  Convey URL parameters for page state. Member sequence is $page, $sort, $dir, $crit, $search_method
 * @return string HTML
 */

	function dLink($event, $step, $thing, $value, $verify = '', $thing2 = '', $thing2val = '', $get = '', $remember = null)
	{
		if ($remember)
		{
			list($page, $sort, $dir, $crit, $search_method) = $remember;
		}

		if ($get)
		{
			$url = '?event='.$event.a.'step='.$step.a.$thing.'='.urlencode($value).a.'_txp_token='.form_token();

			if ($thing2)
			{
				$url .= a.$thing2.'='.urlencode($thing2val);
			}

			if ($remember)
			{
				$url .= a.'page='.$page.a.'sort='.$sort.a.'dir='.$dir.a.'crit='.$crit.a.'search_method='.$search_method;
			}

			return join('', array(
				'<a href="'.$url.'" class="dlink destroy" title="'.gTxt('delete').'" onclick="return verify(\'',
				($verify) ? gTxt($verify) : gTxt('confirm_delete_popup'),
				'\')">×</a>'
			));
		}

		return join('', array(
			'<form method="post" action="index.php" onsubmit="return confirm(\''.gTxt('confirm_delete_popup').'\');">',
			 fInput('submit', '', '×', 'destroy', gTxt('delete')),
			 eInput($event).
			 sInput($step),
			 hInput($thing, $value),
			 ($thing2) ? hInput($thing2, $thing2val) : '',
			 ($remember) ? hInput('page', $page) : '',
			 ($remember) ? hInput('sort', $sort) : '',
			 ($remember) ? hInput('dir', $dir) : '',
			 ($remember) ? hInput('crit', $crit) : '',
			 ($remember) ? hInput('search_method', $search_method) : '',
			 n.tInput(),
			 '</form>'
		));
	}

/**
 * Renders a link with two addition URL parameters.
 *
 * This function can be used for invoking an admin-side "add" action
 * while taking up to two additional URL parameters.
 *
 * @param  string $event  Event
 * @param  string $step   Step
 * @param  string $thing  URL parameter key #1
 * @param  string $value  URL parameter value #1
 * @param  string $thing2 URL parameter key #2
 * @param  string $value2 URL parameter value #2
 * @return string HTML
 */

	function aLink($event, $step, $thing, $value, $thing2, $value2)
	{
		$o = '<a href="?event='.$event.a.'step='.$step.a.'_txp_token='.form_token().
			a.$thing.'='.urlencode($value).a.$thing2.'='.urlencode($value2).'"';
		$o.= ' class="alink">+</a>';
		return $o;
	}

/**
 * Renders a link invoking an admin-side "previous/next article" action.
 *
 * @param  string $name    Link text
 * @param  string $event   Event
 * @param  string $step    Step
 * @param  int    $id      ID of target Textpattern object (article,...)
 * @param  string $titling HTML title attribute
 * @param  string $rel     HTML rel attribute
 * @return string HTML
 */

	function prevnext_link($name, $event, $step, $id, $titling = '', $rel = '')
	{
		return '<a href="?event='.$event.a.'step='.$step.a.'ID='.$id.
			'" class="navlink"'.($titling ? ' title="'.$titling.'"' : '').($rel ? ' rel="'.$rel.'"' : '').'>'.$name.'</a>';
	}

/**
 * Renders a link invoking an admin-side "previous/next page" action.
 *
 * @param  string $event         Event
 * @param  int    $page          Target page number
 * @param  string $label         Link text
 * @param  string $type          Direction, either "prev" or "next"
 * @param  string $sort          Sort field
 * @param  string $dir           Sort direction, either "asc" or "desc"
 * @param  string $crit          Search criterion
 * @param  string $search_method Search method
 * @param  string $step          Step
 * @return string HTML
 */

	function PrevNextLink($event, $page, $label, $type, $sort = '', $dir = '', $crit = '', $search_method = '', $step = 'list')
	{
		return '<a href="?event='.$event.a.'step='.$step.a.'page='.$page.
			($sort ? a.'sort='.$sort : '').
			($dir ? a.'dir='.$dir : '').
			(($crit != '') ? a.'crit='.$crit : '').
			($search_method ? a.'search_method='.$search_method : '').
			'" class="navlink" rel="'.$type.'">'.
			$label.
			'</a>';
	}

/**
 * Renders a page navigation form.
 *
 * @param  string $event         Event
 * @param  int    $page          Current page number
 * @param  int    $numPages	     Total pages
 * @param  string $sort          Sort criterion
 * @param  string $dir           Sort direction, either "asc" or "desc"
 * @param  string $crit          Search criterion
 * @param  string $search_method Search method
 * @param  int    $total	     Total search term hit count [0]
 * @param  int    $limit	     First visible search term hit number [0]
 * @param  string $step	         Step
 * @return string HTML
 */

	function nav_form($event, $page, $numPages, $sort, $dir, $crit, $search_method, $total = 0, $limit = 0, $step = 'list')
	{
		global $theme;
		if ($crit != '' && $total > 1)
		{
			$out[] = $theme->announce(
				gTxt('showing_search_results',
					array(
						'{from}'  => (($page - 1) * $limit) + 1,
						'{to}'    => min($total, $page * $limit),
						'{total}' => $total
						)
					)
				);
		}

		if ($numPages > 1)
		{
			$option_list = array();

			for ($i = 1; $i <= $numPages; $i++)
			{
				if ($i == $page)
				{
					$option_list[] = '<option value="'.$i.'" selected="selected">'."$i/$numPages".'</option>';
				}

				else
				{
					$option_list[] = '<option value="'.$i.'">'."$i/$numPages".'</option>';
				}
			}

			$nav = array();

			$nav[] = ($page > 1) ?
				PrevNextLink($event, $page - 1, gTxt('prev'), 'prev', $sort, $dir, $crit, $search_method, $step).sp :
				tag(gTxt('prev'), 'span', ' class="navlink-disabled" aria-disabled="true"').sp;

			$nav[] = '<select name="page" onchange="submit(this.form);">';
			$nav[] = n.join(n, $option_list);
			$nav[] = n.'</select>';

			$nav[] = ($page != $numPages) ?
				sp.PrevNextLink($event, $page + 1, gTxt('next'), 'next', $sort, $dir, $crit, $search_method, $step) :
				sp.tag(gTxt('next'), 'span', ' class="navlink-disabled" aria-disabled="true"');

			$out[] = '<form class="nav-form" method="get" action="index.php">'.
				n.eInput($event).
				n.sInput($step).
				( $sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
				( ($crit != '') ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '' ).
				'<p class="prev-next">'.
				join('', $nav).
				'</p>'.
				n.tInput().
				n.'</form>';
		}
		else
		{
			$out[] = graf($page.'/'.$numPages, ' class="prev-next"');
		}

		return join(n, $out);
	}

/**
 * Wraps a collapsible region and group structure around content.
 *
 * @param  string $id        HTML id attribute for the region wrapper and ARIA label
 * @param  string $content   Content to wrap. If empty, only the outer wrapper will be rendered
 * @param  string $anchor_id HTML id attribute for the collapsible wrapper
 * @param  string $label     L10n label name
 * @param  string $pane      Pane reference for maintaining toggle state in prefs. Prefixed with 'pane_', suffixed with '_visible'
 * @param  string $class     CSS class name to apply to wrapper
 * @param  string $role      ARIA role name
 * @param  string $help      Help text item
 * @return string HTML
 * @since  4.6.0
 */

	function wrapRegion($id, $content = '', $anchor_id = '', $label = '', $pane = '', $class = '', $role = 'region', $help = '')
	{
		$heading = gTxt($label);
		$help_link = ($help) ? n.popHelp($help) : '';

		$class = ($class) ? ' '.trim($class) : '';
		$display_state = ($role == 'region') ? ' role="group"' : '';
		$role = ($role) ? ' role="'.$role.'"' : '';
		$pane_ref = $heading_class = '';

		if ($anchor_id && $pane)
		{
			$pane_ref = get_pref('pane_'.$pane.'_visible');
			$heading_class = ' class="txp-summary' . ($pane_ref ? ' expanded' : '') . '"';
			$display_state = ' role="group" id="'.$anchor_id.'" class="toggle" style="display:' . ($pane_ref ? 'block' : 'none') . '"';
			$heading = '<a href="#'.$anchor_id.'" role="button">' . $heading . '</a>';
			$help_link = '';
		}

		$out = array();

		$out[] = n.'<section'.$role.' id="'.$id.'" class="txp-details'.$class.'"' . ($content ? ' aria-labelledby="'.$id.'-label"' : '' ) . '>';

		if ($content)
		{
			$out[] = hed($heading.$help_link, 3, ' id="'.$id.'-label"'.$heading_class);
			$out[] = '<div'.$display_state.'>';
			$out[] = $content;
			$out[] = '</div>';
		}

		$out[] = '</section>';

		return join(n, $out);
	}

/**
 * Wraps a region and group structure around content.
 *
 * @param  string $name    HTML id attribute for the group wrapper and ARIA label
 * @param  string $content Content to wrap
 * @param  string $label   L10n label name
 * @param  string $class   CSS class name to apply to wrapper
 * @param  string $help    Help text item
 * @return string HTML
 * @see    wrapRegion()
 * @since  4.6.0
 */

	function wrapGroup($id, $content, $label, $class = '', $help = '')
	{
		return wrapRegion($id, $content, '', $label, '', $class, 'region', $help);
	}

/**
 * Renders start of a layout &lt;table&gt; element.
 *
 * @return     string HTML
 * @deprecated 4.4.0
 */

	function startSkelTable()
	{
		return
		'<table width="300" cellpadding="0" cellspacing="0" style="border:1px #ccc solid">';
	}

/**
 * Renders start of a layout &lt;table&gt; element.
 *
 * @param  string $id    HTML id attribute
 * @param  string $align HTML align attribute
 * @param  string $class HTML class attribute
 * @param  int    $p     HTML cellpadding attribute
 * @param  int    $w     HTML width atttribute
 * @return string HTML
 * @example
 * startTable().
 * tr(td('column') . td('column')).
 * tr(td('column') . td('column')).
 * endTable();
 */

	function startTable($id = '', $align = '', $class = '', $p = 0, $w = 0)
	{
		$atts = join_atts(array(
			'id' => $id,
			'align' => $align,
			'class' => $class,
			'cellpadding' => (int) $p,
			'width' => (int) $w,
		));

		return '<table'.$atts.'>'.n;
	}

/**
 * Renders closing &lt;/table&gt; tag.
 *
 * @return string HTML
 */

	function endTable()
	{
		return n.'</table>'.n;
	}

/**
 * Renders &lt;tr&gt; elements from input parameters.
 *
 * Takes a list of arguments containing each making a row.
 *
 * @return string HTML
 * @example
 * stackRows(
 * 	td('cell') . td('cell'),
 *  td('cell') . td('cell')
 * );
 */

	function stackRows()
	{
		foreach (func_get_args() as $a)
		{
			$o[] = tr($a);
		}
		return join('', $o);
	}

/**
 * Renders a &lt;td&gt; element.
 *
 * @param  string $content Cell content
 * @param  int    $width   HTML width attribute
 * @param  string $class   HTML class attribute
 * @param  string $id      HTML id attribute
 * @return string HTML
 */

	function td($content = '', $width = 0, $class = '', $id = '')
	{
		return tda($content, array(
			'width' => (int) $width,
			'class' => $class,
			'id' => $id,
		));
	}

/**
 * Renders a &lt;td&gt; element with attributes.
 *
 * @param  string       $content Cell content
 * @param  string|array $atts    Cell attributes
 * @return string       HTML
 */

	function tda($content, $atts = '')
	{
		$content = ($content === '') ? sp : $content;
		return tag($content, 'td', $atts);
	}

/**
 * Renders a &lt;td&gt; element with attributes.
 *
 * This function is identical to tda().
 *
 * @param  string       $content Cell content
 * @param  string|array $atts    Cell attributes
 * @return string       HTML
 * @access private
 * @see    tda()
 */

	function tdtl($content, $atts = '')
	{
		return tda($content, $atts);
	}

/**
 * Renders a &lt;tr&gt; element with attributes.
 *
 * @param  string       $content Row content
 * @param  string|array $atts    Row attributes
 * @return string       HTML
 */

	function tr($content, $atts = '')
	{
		return tag($content, 'tr', $atts);
	}

/**
 * Renders a &lt;td&gt; element with top/left text orientation, colspan and other attributes.
 *
 * @param  string $content Cell content
 * @param  int    $span    Cell colspan attribute
 * @param  int    $width   Cell width attribute
 * @param  string $class   Cell class attribute
 * @return string HTML
 */

	function tdcs($content, $span, $width = 0, $class = '')
	{
		return tda($content, array(
			'colspan' => (int) $span,
			'width' => (int) $width,
			'class' => $class,
		));
	}

/**
 * Renders a &lt;td&gt; element with a rowspan attribute.
 *
 * @param  string $content Cell content
 * @param  int    $span    Cell rowspan attribute
 * @param  int    $width   Cell width attribute
 * @param  string $class   Cell class attribute 
 * @return string HTML
 */

	function tdrs($content, $span, $width = 0, $class = '')
	{
		return tda($content, array(
			'rowspan' => (int) $span,
			'width' => (int) $width,
			'class' => $class,
		));
	}

/**
 * Renders a form label inside a table cell.
 *
 * @param  string $text     Label text
 * @param  string $help     Help text
 * @param  string $label_id HTML "for" attribute, i.e. id of corresponding form element
 * @return string HTML
 */

	function fLabelCell($text, $help = '', $label_id = '')
	{
		$help = ($help) ? popHelp($help) : '';

		$cell = gTxt($text).' '.$help;

		if ($label_id)
		{
			$cell = '<label for="'.$label_id.'">'.$cell.'</label>';
		}

		return tda($cell, ' class="cell-label"');
	}

/**
 * Renders a form input inside a table cell.
 *
 * @param  string $name     HTML name attribute
 * @param  string $var      Input value
 * @param  int    $tabindex HTML tabindex attribute
 * @param  int    $size     HTML size attribute
 * @param  string $help     Help text
 * @param  string $id       HTML id attribute
 * @return string HTML
 */

	function fInputCell($name, $var = '', $tabindex = 0, $size = 0, $help = '', $id = '')
	{
		$pop = ($help) ? sp.popHelp($name) : '';

		return tda(fInput('text', $name, $var, '', '', '', $size, $tabindex, $id).$pop);
	}

/**
 * Renders a name-value input control with label.
 *
 * @param  string $name        HTML id / name attribute
 * @param  string $input       complete input control widget (result of fInput(), yesnoRadio(), etc)
 * @param  string $label       Label
 * @param  string $help        pophelp text item
 * @param  string $class       CSS class name to apply to wrapper
 * @param  string $wraptag_val Tag to wrap the value in. If set to '', no wrapper is used (useful for textareas)
 * @return string HTML
 */

	function inputLabel($name, $input, $label = '', $help = '', $class = '', $wraptag_val = 'span')
	{
		$help = ($help) ? sp.popHelp($help) : '';
		$class = ($class) ? $class : 'edit-'.str_replace('_', '-', $name);
		$label_open = ($label) ? '<label for="'.$name.'">' : '';
		$label_close = ($label) ? '</label>' : '';
		$label = ($label) ? $label : $name;
		$wrapval_open = ($wraptag_val) ? '<'.$wraptag_val.' class="edit-value">' : '';
		$wrapval_close = ($wraptag_val) ? '</'.$wraptag_val.'>' : '';

		return graf(
			'<span class="edit-label">'.$label_open.gTxt($label).$label_close.$help.'</span>'.n.
			$wrapval_open.$input.$wrapval_close
		, ' class="'.$class.'"');
	}

/**
 * Renders anything as an XML element.
 *
 * @param  string       $content Enclosed content
 * @param  string       $tag     The tag without brackets
 * @param  string|array $atts    The element's HTML attributes
 * @return string       HTML
 * @example
 * echo tag('Link text', 'a', array('href' => '#', 'class' => 'warning'));
 */

	function tag($content, $tag, $atts = '')
	{
		return ('' !== $content) ? '<'.$tag.join_atts($atts).'>'.$content.'</'.$tag.'>' : '';
	}

/**
 * Renders anything as a HTML void element.
 *
 * @param  string       $tag  The tag without brackets
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 * @since  4.6.0
 * @example
 * echo tag_void('input', array('name' => 'name', 'type' => 'text'));
 */

	function tag_void($tag, $atts = '')
	{
		return '<'.$tag.join_atts($atts).'>';
	}

/**
 * Renders a &lt;p&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 * @example
 * echo graf('This a paragraph.');
 */

	function graf($item, $atts = '')
	{
		return tag($item, 'p', $atts);
	}

/**
 * Renders a &lt;hx&gt; element.
 *
 * @param  string       $item  The Enclosed content
 * @param  int          $level Heading level 1...6
 * @param  string|array $atts  HTML attributes
 * @return string       HTML
 * @example
 * echo hed('Heading', 2);
 */

	function hed($item, $level, $atts = '')
	{
		return tag($item, 'h'.$level, $atts);
	}

/**
 * Renders an &lt;a&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string       $href The link target
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function href($item, $href, $atts = '')
	{
		if (is_array($atts))
		{
			$atts['href'] = $href;
		}
		else
		{
			$atts .= ' href="'.$href.'"';
		}

		return tag($item, 'a', $atts);
	}

/**
 * Renders a &lt;strong&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function strong($item, $atts = '')
	{
		return tag($item, 'strong', $atts);
	}

/**
 * Renders a &lt;span&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function span($item, $atts = '')
	{
		return tag($item, 'span', $atts);
	}

/**
 * Renders a &lt;pre&gt; element.
 *
 * @param  string $item The input string
 * @return string HTML
 * @example
 * echo htmlPre('&lt;?php echo "Hello World"; ?&gt;');
 */

	function htmlPre($item)
	{
		return '<pre>'.tag($item, 'code').'</pre>';
	}

/**
 * Renders a HTML comment (&lt;!-- --&gt;) element.
 *
 * @param  string $item The input string
 * @return string HTML
 * @example
 * echo comment('Some HTML comment.');
 */

	function comment($item)
	{
		return '<!-- '.str_replace('--', '&shy;&shy;', $item).' -->';
	}

/**
 * Renders a &lt;small&gt element.
 *
 * @param  string       $item The input string
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function small($item, $atts = '')
	{
		return tag($item, 'small', $atts);
	}

/**
 * Renders a table data row from an array of content => width pairs.
 *
 * @param  array        $array Array of content => width pairs
 * @param  string|array $atts  Table row atrributes
 * @return string       A HTML table row
 */

	function assRow($array, $atts ='')
	{
		foreach ($array as $a => $b)
		{
			$o[] = tda($a, ' width="'.$b.'"');
		}
		return tr(join(n.t, $o), $atts);
	}

/**
 * Renders a table head row from an array of strings.
 *
 * Takes an argument list of head text strings. i18n is applied to the strings.
 *
 * @return string HTML
 */

	function assHead()
	{
		$array = func_get_args();
		foreach ($array as $a)
		{
			$o[] = hCell(gTxt($a), '', ' scope="col"');
		}
		return tr(join('', $o));
	}

/**
 * Renders the ubiquitious popup help button.
 *
 * @param  string $help_var Help topic
 * @param  int    $width    Popup window width
 * @param  int    $height   Popup window height
 * @param  string $class    HTML class
 * @return string HTML
 */

	function popHelp($help_var, $width = 0, $height = 0, $class = 'pophelp')
	{
		$ui = '<a role="button" rel="help" target="_blank"'.
			' href="'.HELP_URL.'?item='.$help_var.a.'language='.LANG.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;"'. ($class ? ' class="'.$class.'"' : '') .'>?</a>';
		return pluggable_ui('admin_help', $help_var, $ui, compact('help_var', 'width', 'height', 'class'));
	}

/**
 * Renders the ubiquitious popup help button with a little less visual noise.
 *
 * @param  string $help_var Help topic
 * @param  int    $width    Popup window width
 * @param  int    $height   Popup window height
 * @return string HTML
 */

	function popHelpSubtle($help_var, $width = 0, $height = 0)
	{
		return popHelp($help_var, $width, $height, 'pophelpsubtle');
	}

/**
 * Renders a link that opens a popup tag help window.
 *
 * @param  string $var    Tag name
 * @param  string $text   Link text
 * @param  int    $width  Popup window width
 * @param  int    $height Popup window height
 * @return string HTML
 */

	function popTag($var, $text, $width = 0, $height = 0)
	{
		return '<a target="_blank"'.
			' href="?event=tag'.a.'tag_name='.$var.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;">'.$text.'</a>';
	}

/**
 * Renders a list of tag builder links.
 *
 * @param  string $type Tag type
 * @return string HTML
 */

	function popTagLinks($type)
	{
		include txpath.'/lib/taglib.php';

		$arname = $type.'_tags';

		$out = array();

		$out[] = n.'<ul class="plain-list">';

		foreach ($$arname as $a)
		{
			$out[] = n.t.tag(popTag($a,gTxt('tag_'.$a)), 'li');
		}

		$out[] = n.'</ul>';

		return join('', $out);
	}

/**
 * Renders an admin-side message text.
 *
 * @param  string $thing    Subject
 * @param  string $thething Predicate (strong)
 * @param  string $action   Object
 * @return string HTML
 */

	function messenger($thing, $thething = '', $action = '')
	{
		return gTxt($thing).' '.strong($thething).' '.gTxt($action);
	}

/**
 * Renders a multi-edit form listing editing methods.
 *
 * @param  array   $options       array('value' => array( 'label' => '', 'html' => '' ),...)
 * @param  string  $event         Event
 * @param  string  $step          Step
 * @param  int     $page          Page number
 * @param  string  $sort          Column sorted by
 * @param  string  $dir           Sorting direction
 * @param  string  $crit          Search criterion
 * @param  string  $search_method Search method
 * @return string  HTML
 * @example
 * echo form(
 * 	multi_edit(array(
 * 		'feature' => array('label' => 'Feature', 'html' => yesnoRadio('is_featured', 1)),
 * 		'delete'  => array('label' => 'Delete'),
 * 	))
 * );
 */

	function multi_edit($options, $event = null, $step = null, $page = '', $sort = '', $dir = '', $crit = '', $search_method = '')
	{
		$html = $methods = array();
		$methods[''] = gTxt('with_selected_option');

		if ($event === null)
		{
			global $event;
		}

		if ($step === null)
		{
			$step = $event.'_multi_edit';
		}

		callback_event_ref($event.'_ui', 'multi_edit_options', 0, $options);

		foreach ($options as $value => $option)
		{
			if (is_array($option))
			{
				$methods[$value] = $option['label'];

				if (isset($option['html']))
				{
					$html[$value] = '<div class="multi-option multi-option-'.txpspecialchars($value).'">'.$option['html'].'</div>';
				}
			}
			else
			{
				$methods[$value] = $option;
			}
		}

		return '<div class="multi-edit">'.
			n.selectInput('edit_method', $methods, '').
			n.eInput($event).
			n.sInput($step).
			n.hInput('page', $page).
			($sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
			($crit !== '' ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '').
			n.implode('', $html).
			n.fInput('submit', '', gTxt('go')).
			n.'</div>';
	}

/**
 * Renders a form to select various amounts to page lists by.
 *
 * @param  string $event Event
 * @param  int    $val   Current setting
 * @return string HTML
 */

	function pageby_form($event, $val)
	{
		$vals = array(
			15  => 15,
			25  => 25,
			50  => 50,
			100 => 100
		);

		$select_page = selectInput('qty', $vals, $val, '', 1);

		// proper localisation
		$page = str_replace('{page}', $select_page, gTxt('view_per_page'));

		return form(
			'<p>'.
				$page.
				eInput($event).
				sInput($event.'_change_pageby').
			'</p>'
		, '', '', 'post', 'pageby');
	}

/**
 * Renders a file upload form via the "$event_ui" > "upload_form" pluggable UI.
 *
 * @param  string $label         File name label. May be empty
 * @param  string $pophelp       Help item
 * @param  string $step          Step
 * @param  string $event         Event
 * @param  string $id            File id
 * @param  int    $max_file_size Maximum allowed file size
 * @param  string $label_id      HTML id attribute for the filename input element
 * @param  string $class         HTML class attribute for the form element
 * @return string HTML
 */

	function upload_form($label, $pophelp = '', $step, $event, $id = '', $max_file_size = 1000000, $label_id = '', $class = 'upload-form')
	{
		global $sort, $dir, $page, $search_method, $crit;

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		$class = ($class) ? ' class="'.$class.'"' : '';
		$p_class = 'edit-'. (($label_id) ? str_replace('_', '-', $label_id) : $event.'-upload');
		$label_id = ($label_id) ? $label_id : $event.'-upload';

		$argv = func_get_args();
		return pluggable_ui($event.'_ui', 'upload_form',
			n.n.'<form'.$class.' method="post" enctype="multipart/form-data" action="index.php">'.

			(!empty($max_file_size)? n.hInput('MAX_FILE_SIZE', $max_file_size): '').
			n.eInput($event).
			n.sInput($step).
			n.hInput('id', $id).

			n.hInput('sort', $sort).
			n.hInput('dir', $dir).
			n.hInput('page', $page).
			n.hInput('search_method', $search_method).
			n.hInput('crit', $crit).

			n.graf(
				(($label) ? '<label for="'.$label_id.'">'.$label.'</label>' : '').(($pophelp) ? sp.popHelp($pophelp) : '').n.
					fInput('file', 'thefile', '', '', '', '', '', '', $label_id).n.
					fInput('submit', '', gTxt('upload'))
			, ' class="'.$p_class.'"').

			n.tInput().
			n.'</form>',
			$argv);
	}

/**
 * Renders an admin-side search form.
 *
 * @param  string $event          Event
 * @param  string $step           Step
 * @param  string $crit           Search criterion
 * @param  array  $methods        Valid search methods
 * @param  string $method         Actual search method
 * @param  string $default_method Default search method
 * @return string HTML
 */

	function search_form($event, $step, $crit, $methods, $method, $default_method)
	{
		$method = ($method) ? $method : $default_method;

		return n.n.form(
			graf(
				'<label for="'.$event.'-search">'.gTxt('search').'</label>'.
				n.selectInput('search_method', $methods, $method, '', '', $event.'-search').
				n.fInput('text', 'crit', $crit, 'input-medium', '', '', INPUT_MEDIUM).
				n.eInput($event).
				n.sInput($step).
				n.fInput('submit', 'search', gTxt('go'))
			)
		, '', '', 'get', 'search-form');
	}

/**
 * Renders a dropdown for selecting text filter method preferences.
 *
 * @param  string $name Element name
 * @param  string $val  Current value
 * @param  string $id   HTML id attribute for the select input element
 * @return string HTML
 */

	function pref_text($name, $val, $id = '')
	{
		$id = ($id) ? $id : $name;
		$vals = TextfilterSet::map();
		return selectInput($name, $vals, $val, '', '', $id);
	}

/**
 * Attaches a HTML fragment to a DOM node.
 *
 * @param  string $id        Target DOM node's id
 * @param  string $content   HTML fragment
 * @param  string $noscript  Noscript alternative
 * @param  string $wraptag   Wrapping HTML element
 * @param  string $wraptagid Wrapping element's HTML id
 * @return string HTML/JS
 */

	function dom_attach($id, $content, $noscript = '', $wraptag = 'div', $wraptagid = '')
	{
		$content = escape_js($content);

		$js = <<<EOF
			$(document).ready(function() {
				$('#{$id}').append($('<{$wraptag} />').attr('id', '{$wraptagid}').html('{$content}'));
			});
EOF;

		return script_js($js, (string) $noscript);
	}

/**
 * Renders a &lt:script&gt; element.
 *
 * @param  string     $js    JavaScript code
 * @param  int|string $flags Flags SCRIPT_URL | SCRIPT_ATTACH_VERSION, or noscript alternative if a string.
 * @return string HTML with embedded script element
 * @example
 * echo script_js('/js/script.js', SCRIPT_URL);
 */

	function script_js($js, $flags = '')
	{
		if (is_int($flags))
		{
			if ($flags & SCRIPT_URL)
			{
				if ($flags & SCRIPT_ATTACH_VERSION && strpos(txp_version, '-dev') === false)
				{
					$ext = pathinfo($js, PATHINFO_EXTENSION);

					if ($ext)
					{
						$js = substr($js, 0, (strlen($ext)+1) * -1);
						$ext = '.'.$ext;
					}

					$js .= '.v'.txp_version.$ext;
				}

				return tag(null, 'script', array('src' => $js)).n;
			}
		}

		$js = preg_replace('#<(/?)script#', '\\x3c$1script', $js);

		$out = tag(n.trim($js).n, 'script').n;

		if ($flags)
		{
			$out .= tag(n.trim($flags).n, 'noscript').n;
		}

		return $out;
	}

/**
 * Renders a "Details" toggle checkbox.
 *
 * @param  string $classname Unique identfier. The cookie's name will be derived from this value.
 * @param  bool	  $form      Create as a stand-along &lt;form&gt; element [false]
 * @return string HTML
 */

	function toggle_box($classname, $form = 0)
	{
		$name = 'cb_toggle_'.$classname;
		$i =
			'<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.
			(cs('toggle_'.$classname) ? 'checked="checked" ' : '').
			'class="checkbox" onclick="toggleClassRemember(\''.$classname.'\');" />'.
			' <label for="'.$name.'">'.gTxt('detail_toggle').'</label> '.
			script_js("setClassRemember('".$classname."');addEvent(window, 'load', function(){setClassRemember('".$classname."');});");
		if ($form)
		{
			return n.form($i);
		}
		else
		{
			return n.$i;
		}
	}

/**
 * Renders a checkbox to set/unset a browser cookie.
 *
 * @param  string $classname Label text. The cookie's name will be derived from this value.
 * @param  bool   $form      Create as a stand-along &lt;form&gt; element [true]
 * @return string HTML
 */

	function cookie_box($classname, $form = 1)
	{
		$name = 'cb_'.$classname;
		$val = cs('toggle_'.$classname) ? 1 : 0;

		$i =
			'<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.
			($val ? 'checked="checked" ' : '').
			'class="checkbox" onclick="setClassRemember(\''.$classname.'\','.(1-$val).');submit(this.form);" />'.
			' <label for="'.$name.'">'.gTxt($classname).'</label> ';

		if ($form)
		{
			$args = empty($_SERVER['QUERY_STRING']) ? '' : '?'.txpspecialchars($_SERVER['QUERY_STRING']);
			return '<form class="'.$name.'" method="post" action="index.php'.$args.'">'.$i.eInput(gps('event')).n.tInput().'</form>';
		}
		else
		{
			return n.$i;
		}
	}

/**
 * Renders a &lt;fieldset&gt; element.
 *
 * @param  string $content Enclosed content
 * @param  string $legend  Legend text
 * @param  string $id      HTML id attribute
 * @return string HTML
 */

	function fieldset($content, $legend = '', $id = '')
	{
		return tag(trim(tag($legend, 'legend').n.$content), 'fieldset', array('id' => $id));
	}

/**
 * Renders a link element to hook up txpAsyncHref() with request parameters.
 *
 * See this function's JavaScript companion, txpAsyncHref(), in textpattern.js.
 *
 * @param  string       $item  Link text
 * @param  array        $parms Request parameters; array keys are 'event', 'step', 'thing', 'property'
 * @param  string|array $atts  HTML attributes
 * @return string HTML
 * @since  4.5.0
 * @example
 * echo asyncHref('Disable', array(
 * 	'event'    => 'myEvent',
 * 	'step'     => 'myStep',
 * 	'thing'    => 'status',
 * 	'property' => 'disable',
 * ));
 */

	function asyncHref($item, $parms, $atts = '')
	{
		global $event, $step;

		$parms = lAtts(array(
			'event'    => $event,
			'step'     => $step,
			'thing'    => '',
			'property' => '',
		), $parms);

		$class = $parms['step'].' async';

		if (is_array($atts))
		{
			$atts['class'] = $class;
		}
		else
		{
			$atts .= ' class="'.txpspecialchars($class).'"';
		}

		return href($item, join_qs($parms), $atts);
	}

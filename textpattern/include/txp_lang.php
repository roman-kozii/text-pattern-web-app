<?php

/*
This is Textpattern

Copyright 2012 The Textpattern Development Team
textpattern.com
All rights reserved.

Use of this software indicates acceptance of the Textpattern license agreement


@since 4.6.0
*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

include_once txpath.'/lib/txplib_update.php';

if ($event == 'lang') {
	require_privs('lang');

	$available_steps = array(
		'get_language'    => true,
		'get_textpack'    => true,
		'remove_language' => true,
		'list_languages'  => false,
	);

	if ($step && bouncer($step, $available_steps)) {
		$step();
	} else {
		list_languages();
	}
}

/**
 * Generate a &lt;select&gt; element of installed languages
 *
 * @param  string $name The HTML name and ID to assign to the select control
 * @param  string $val  The currently active language identifier (en-gb, fr-fr, ...)
 * @return string HTML
 */
function languages($name, $val)
{
	$installed_langs = safe_column('lang', 'txp_lang', "1 = 1 group by lang");

	$vals = array();

	foreach ($installed_langs as $lang)
	{
		$vals[$lang] = safe_field('data', 'txp_lang', "name = '".doSlash($lang)."' AND lang = '".doSlash($lang)."'");

		if (trim($vals[$lang]) == '')
		{
			$vals[$lang] = $lang;
		}
	}

	asort($vals);
	reset($vals);

	$out = n.'<select id="'.$name.'" name="'.$name.'" class="languages">';

	foreach ($vals as $avalue => $alabel)
	{
		$selected = ($avalue == $val || $alabel == $val) ?
			' selected="selected"' :
			'';

		$out .= n.t.'<option value="'.txpspecialchars($avalue).'"'.$selected.'>'.txpspecialchars($alabel).'</option>'.n;
	}

	$out .= n.'</select>';

	return $out;
}

/**
 * Generate a table of every language that Textpattern supports
 *
 * @param string $message The feedback message to display as a result of user interaction
 * Optional:
 * ($_POST) $force string Displays RPC connection errors if set to anything other than 'file'
 */
function list_languages($message='')
{
	global $prefs, $locale, $textarray;
	require_once txpath.'/lib/IXRClass.php';

	// Select and save active language
	if (!$message && ps('step') == 'list_languages' && ps('language'))
	{
		$locale = doSlash(getlocale(ps('language')));
		safe_update('txp_prefs', "val='". doSlash(ps('language')) ."'", "name='language'");
		safe_update('txp_prefs', "val='". $locale ."'", "name='locale'");
		$textarray = load_lang(doSlash(ps('language')));
		$locale = setlocale(LC_ALL, $locale);
		$message = gTxt('preferences_saved');
	}
	$active_lang = safe_field('val', 'txp_prefs', "name='language'");
	$lang_form = '<div id="language_control" class="txp-control-panel">'.
			form(
				graf(
					gTxt('active_language').
					languages('language',$active_lang).n.
					fInput('submit','Submit',gTxt('save'),'publish').
					eInput('lang').sInput('list_languages')
				)
			).'</div>';

	$client = new IXR_Client(RPC_SERVER);
//	$client->debug = true;

	$available_lang = array();
	$rpc_connect = false;
	$show_files = false;

	// Get items from RPC
	@set_time_limit(90); // TODO: 90 seconds: seriously?
	if ($client->query('tups.listLanguages', $prefs['blog_uid']))
	{
		$rpc_connect = true;
		$response = $client->getResponse();
		foreach ($response as $language)
		{
			$available_lang[$language['language']]['rpc_lastmod'] = gmmktime($language['lastmodified']->hour,$language['lastmodified']->minute,$language['lastmodified']->second,$language['lastmodified']->month,$language['lastmodified']->day,$language['lastmodified']->year);
		}
	}
	elseif (gps('force') != 'file')
	{
		$msg = gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->";
	}

	// Get items from Filesystem
	$files = get_lang_files();

	if ( is_array($files) && !empty($files) )
	{
		foreach ($files as $file)
		{
			if ($fp = @fopen(txpath.DS.'lang'.DS.$file,'r'))
			{
				$name = str_replace('.txt','',$file);
				$firstline = fgets($fp, 4069);
				fclose($fp);
				if (strpos($firstline,'#@version') !== false)
				{
					@list($fversion,$ftime) = explode(';',trim(substr($firstline,strpos($firstline,' ',1))));
				}
				else
				{
					$fversion = $ftime = NULL;
				}

				$available_lang[$name]['file_note'] = (isset($fversion)) ? $fversion : 0;
				$available_lang[$name]['file_lastmod'] = (isset($ftime)) ? $ftime : 0;
			}
		}
	}

	// Get installed items from the database
	// We need a value here for the language itself, not for each one of the rows
	$rows = safe_rows('lang, UNIX_TIMESTAMP(MAX(lastmod)) as lastmod','txp_lang',"1 GROUP BY lang ORDER BY lastmod DESC");
	$installed_lang = array();
	foreach ($rows as $language)
	{
		$available_lang[$language['lang']]['db_lastmod'] = $language['lastmod'];
		if ($language['lang'] != $active_lang) {
			$installed_lang[] = $language['lang'];
		}
	}

	$list = '';

	// Create the language table components
	foreach ($available_lang as $langname => $langdat)
	{
		$file_updated = ( isset($langdat['db_lastmod']) && @$langdat['file_lastmod'] > $langdat['db_lastmod']);
		$rpc_updated = ( @$langdat['rpc_lastmod'] > @$langdat['db_lastmod']);

		$rpc_install = tda(
							($rpc_updated)
							? strong(
									eLink(
										'lang',
										'get_language',
										'lang_code',
										$langname,
										(isset($langdat['db_lastmod'])
											? gTxt('update')
											: gTxt('install')
										),
										'updating',
										isset($langdat['db_lastmod']),
										''
									)
								).
								n.'<span class="date modified">'.safe_strftime('%d %b %Y %X',@$langdat['rpc_lastmod']).'</span>'
							: (
									(isset($langdat['rpc_lastmod'])
										? gTxt('updated')
										: '-'
									).
									(isset($langdat['db_lastmod'])
										? n.'<span class="date modified">'.safe_strftime('%d %b %Y %X',$langdat['db_lastmod']).'</span>'
										: ''
									)
								)
							,(isset($langdat['db_lastmod']) && $rpc_updated)
								? ' class="highlight lang-value"'
								: ' class="lang-value"'
							);

		$lang_file = tda(
							(isset($langdat['file_lastmod']))
							? strong(
								eLink(
									'lang',
									'get_language',
									'lang_code',
									$langname,
									(
										($file_updated)
										? gTxt('update')
										: gTxt('install')
									),
									'force',
									'file',
									''
								)
							).
							n.'<span class="date '.($file_updated ? 'created' : 'modified').'">'.safe_strftime($prefs['archive_dateformat'],$langdat['file_lastmod']).'</span>'

							: '-'
						, ' class="lang-value languages_detail'.((isset($langdat['db_lastmod']) && $rpc_updated) ? ' highlight' : '').'"'
						);

		$list .= tr(
			// Lang-Name & Date
			hCell(
				gTxt($langname)
				, ''
				,(isset($langdat['db_lastmod']) && $rpc_updated)
						? ' scope="row" class="highlight lang-label"'
						: ' scope="row" class="lang-label"'
				).n.
			$rpc_install.n.
			$lang_file.n.
			tda( (in_array($langname, $installed_lang) ? dLink('lang', 'remove_language', 'lang_code', $langname, 1) : '-'), ' class="languages_detail'.((isset($langdat['db_lastmod']) && $rpc_updated) ? ' highlight' : '').'"')
		).n.n;
	}

	// Output table + content
	pagetop(gTxt('tab_languages'), $message);

	echo '<h1 class="txp-heading">', gTxt('tab_languages'), '</h1>',
		n, '<div id="language_container" class="txp-container">';

	if (isset($msg) && $msg)
	{
		echo tag($msg, 'p', ' class="error lang-msg"');
	}

	echo $lang_form,
		n, '<div class="txp-listtables">',
		n, startTable('', '', 'txp-list'),
		n, '<thead>',
		n, tr(
			hCell(gTxt('language'), '', ' scope="col"').
			hCell(gTxt('from_server').n.popHelp('install_lang_from_server'), '', ' scope="col"').
			hCell(gTxt('from_file').n.popHelp('install_lang_from_file'), '', ' scope="col" class="languages_detail"').
			hCell(gTxt('remove_lang').n.popHelp('remove_lang'), '', ' scope="col" class="languages_detail"')
		),
		n, '</thead>',

		n, '<tbody>', $list, '</tbody>',
		n, endTable(),
		n, '</div>',

		graf(
			toggle_box('languages_detail'),
			' class="detail-toggle"'
		),

		hed(gTxt('install_from_textpack'), 3),
		n, form(
			graf(
				'<label for="textpack-install">'.gTxt('install_textpack').'</label>'.n.
				popHelp('get_textpack').n.
				'<textarea id="textpack-install" class="code" name="textpack" cols="'.INPUT_LARGE.'" rows="'.INPUT_XSMALL.'"></textarea>'.n.
				fInput('submit', 'install_new', gTxt('upload')).
				eInput('lang').
				sInput('get_textpack')
			)
		, '', '', 'post', 'edit-form', '', 'text_uploader'),

		n, '</div>'; // end language_container
}

/**
 * Fetch language strings for the given lang code from the RPC server or file
 *
 * Requires:
 * ($_POST) $lang_code string Language designator (en-gb, de-de, ...)
 * Optional:
 * ($_POST) $force    string Whether to fetch the data from 'file'
 * ($_POST) $updating bool   Whether the current action is an update (1) or install (0/omitted)
 * @return HTML Language table
 */
function get_language()
{
	global $prefs, $textarray;
	require_once txpath.'/lib/IXRClass.php';
	$lang_code = gps('lang_code');

	$client = new IXR_Client(RPC_SERVER);
//	$client->debug = true;

	@set_time_limit(90); // TODO: 90 seconds: seriously?
	if (gps('force') == 'file' || !$client->query('tups.getLanguage', $prefs['blog_uid'], $lang_code))
	{
		if ( (gps('force') == 'file' || gps('updating') !== '1') && install_language_from_file($lang_code) )
		{
			if (defined('LANG'))
			{
				$textarray = load_lang(LANG);
			}
			callback_event('lang_installed', 'file', false, $lang_code);

			return list_languages(gTxt($lang_code).sp.gTxt('updated'));
		}
		else
		{
			pagetop(gTxt('installing_language'));
			echo tag( gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->"
					,'p',' class="error lang-msg"' );
		}
	}
	else
	{
		$response = $client->getResponse();
		$lang_struct = unserialize($response);
		if ($lang_struct === false) {
			$errors = $size = 1;
		}
		else
		{
			function install_lang_key(&$value, $key)
			{
				extract(gpsa(array('lang_code','updating')));
				$exists = safe_field('name','txp_lang',"name='".doSlash($value['name'])."' AND lang='".doSlash($lang_code)."'");
				$q = "name='".doSlash($value['name'])."', event='".doSlash($value['event'])."', data='".doSlash($value['data'])."', lastmod='".doSlash(strftime('%Y%m%d%H%M%S',$value['uLastmod']))."'";

				if ($exists)
				{
					$value['ok'] = safe_update('txp_lang', $q, "owner = '".doSlash(LANG_OWNER_SYSTEM)."' AND lang='".doSlash($lang_code)."' AND name='".doSlash($value['name'])."'");
				}
				else
				{
					$value['ok'] = safe_insert('txp_lang', $q.", lang='".doSlash($lang_code)."'");
				}
			}

			array_walk($lang_struct,'install_lang_key');
			$size = count($lang_struct);
			$errors = 0;
			for($i=0; $i < $size ; $i++)
			{
				$errors += ( !$lang_struct[$i]['ok'] );
			}

			if (defined('LANG')) {
				$textarray = load_lang(LANG);
			}
		}

		$msg = gTxt($lang_code).sp.gTxt('updated');

		callback_event('lang_installed', 'remote', false, $lang_code);

		if ($errors > 0) {
			$msg = array($msg.sprintf(" (%s errors, %s ok)",$errors, ($size-$errors)), E_ERROR);
		}

		return list_languages($msg);
	}
}

/**
 * Install Textpack strings
 *
 * Requires:
 * ($_POST) $textpack string One or more concatenated Textpack blocks
 * @return string HTML Language table
 * @see txplib_misc.php: install_textpack
 */
function get_textpack()
{
	$textpack = ps('textpack');
	$n = install_textpack($textpack, true);
	return list_languages(gTxt('textpack_strings_installed', array('{count}' => $n)));
}

/**
 * Remove all language strings for the given lang code
 *
 * Requires:
 * ($_POST) $lang_code string Language designator (en-gb, fi-fi, ...)
 * @return HTML Language table
 */
function remove_language()
{
	$lang_code = ps('lang_code');
	$ret = safe_delete('txp_lang', "lang='".doSlash($lang_code)."'");
	if ($ret) {
		callback_event('lang_deleted', '', 0, $lang_code);
		$msg = gTxt($lang_code).sp.gTxt('deleted');
	}
	else
	{
		$msg = gTxt('cannot_delete', array('{thing}' => $lang_code));
	}

	return list_languages($msg);
}

/**
 * Fetch language files available in the filesystem
 *
 * @return array available language filenames
 */
function get_lang_files()
{
	$lang_dir = txpath.DS.'lang'.DS;

	if (!is_dir($lang_dir))
	{
		trigger_error('Lang directory is not a directory: '.$lang_dir, E_USER_WARNING);
		return array();
	}

	if (chdir($lang_dir))
	{
		$files = glob('*.txt');
	}
	return (is_array($files)) ? $files : array();
}

?>
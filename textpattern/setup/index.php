<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2017 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('txpath')) {
    define("txpath", dirname(dirname(__FILE__)));
}

define("txpinterface", "admin");
error_reporting(E_ALL | E_STRICT);
@ini_set("display_errors", "1");

define('MSG_OK', 'alert-block success');
define('MSG_ALERT', 'alert-block warning');
define('MSG_ERROR', 'alert-block error');

include_once txpath.'/lib/class.trace.php';
$trace = new Trace();
include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_misc.php';
include_once txpath.'/vendors/Textpattern/Loader.php';

$loader = new \Textpattern\Loader(txpath.'/vendors');
$loader->register();

$loader = new \Textpattern\Loader(txpath.'/lib');
$loader->register();

if (!isset($_SESSION)) {
    if (headers_sent()) {
        $_SESSION = array();
    } else {
        session_start();
    }
}

include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/include/txp_auth.php';
include_once txpath.'/setup/setup_db.php';

assert_system_requirements();

header("Content-Type: text/html; charset=utf-8");

// Drop trailing cruft.
$_SERVER['PHP_SELF'] = preg_replace('#^(.*index.php).*$#i', '$1', $_SERVER['PHP_SELF']);

// Sniff out the 'textpattern' directory's name '/path/to/site/textpattern/setup/index.php'.
$txpdir = explode('/', $_SERVER['PHP_SELF']);

if (count($txpdir) > 3) {
    // We live in the regular directory structure.
    $txpdir = '/'.$txpdir[count($txpdir) - 3];
} else {
    // We probably came here from a clever assortment of symlinks and DocumentRoot.
    $txpdir = '/';
}

$prefs = array();
$prefs['module_pophelp'] = 1;
$step = ps('step');
$rel_siteurl = preg_replace("#^(.*?)($txpdir)?/setup.*$#i", '$1', $_SERVER['PHP_SELF']);
$rel_txpurl = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');


if (empty($_SESSION['cfg'])) {
    $cfg = @json_decode(file_get_contents('.default.json'), true);
} else {
    $cfg = $_SESSION['cfg'];
}

if (ps('lang')) {
    $cfg['site']['lang'] = ps('lang');
}
if (empty($cfg['site']['lang'])) {
    $cfg['site']['lang'] = TEXTPATTERN_DEFAULT_LANG;
}
$textarray = setup_load_lang($cfg['site']['lang']);

if (empty($cfg['site']['siteurl'])) {
    $protocol = (empty($_SERVER['HTTPS']) || @$_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://';
    if (@$_SERVER['SCRIPT_NAME'] && (@$_SERVER['SERVER_NAME'] || @$_SERVER['HTTP_HOST'])) {
        $cfg['site']['siteurl'] = $protocol.
        ((@$_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']).$rel_siteurl;
    } else {
        $cfg['site']['siteurl'] = $protocol.'mysite.com';
    }
}



/*
    echo "<table><tr><td><pre>";
        print_r($cfg);
    echo "</pre></td><td><pre>";
        print_r($_SESSION);
    echo "</pre></td></tr></table>";
*/




switch ($step) {
    case '':
        step_chooseLang();
        break;
    case 'step_getDbInfo':
        step_getDbInfo();
        break;
    case 'step_getTxpLogin':
        step_getTxpLogin();
        break;
    case 'step_printConfig':
        step_printConfig();
        break;
    case 'step_createTxp':
        step_createTxp();
}
$_SESSION['cfg'] = $cfg;
exit("</main>\n</body>\n</html>");


/**
 * Return the top of page furniture.
 *
 * @param  string $step Name of the current Textpattern step of the setup wizard
 * @return HTML
 */

function preamble()
{
    global $textarray_script, $cfg, $step;

    $out = array();
    $bodyclass = ($step == '') ? ' welcome' : '';
    gTxtScript(array('help'));

    if (isset($cfg['site']['lang']) && !isset($_SESSION['direction'])) {
        $file = Txp::get('\Textpattern\L10n\Lang')->findFilename($cfg['site']['lang']);
        $meta = Txp::get('\Textpattern\L10n\Lang')->fetchMeta($file);
        $_SESSION['direction'] = isset($meta['direction']) ? $meta['direction'] : 'ltr';
    }

    $textDirection = (isset($_SESSION['direction'])) ? ' dir="'.$_SESSION['direction'].'"' : 'ltr';

    $out[] = <<<eod
    <!DOCTYPE html>
    <html lang="en"{$textDirection}>
    <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Setup &#124; Textpattern CMS</title>
eod;

    $out[] = script_js('../vendors/jquery/jquery/jquery.js', TEXTPATTERN_SCRIPT_URL).
        script_js('../vendors/jquery/jquery-ui/jquery-ui.js', TEXTPATTERN_SCRIPT_URL).
        script_js(
            'var textpattern = '.json_encode(array(
                'prefs'         => (object) null,
                'event'         => 'setup',
                'step'          => $step,
                'textarray'     => (object) $textarray_script,
                ), TEXTPATTERN_JSON).';').
        script_js('../textpattern.js', TEXTPATTERN_SCRIPT_URL);

    $out[] = <<<eod
    <link rel="stylesheet" href="../admin-themes/hive/assets/css/textpattern.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    </head>
    <body class="setup{$bodyclass}" id="page-setup">
    <main class="txp-body">
eod;

    return join(n, $out);
}

/**
 * Renders stage 0: welcome/choose language panel.
 */

function step_chooseLang()
{
    echo preamble();
    echo n.'<div class="txp-setup">',
        hed('Welcome to Textpattern CMS', 1),
        n.'<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">',
        langs(),
        graf(fInput('submit', 'Submit', 'Submit', 'publish')),
        sInput('step_getDbInfo'),
        n.'</form>',
        n.'</div>';
}

/**
 * Renders progress meter displayed on stages 1 to 4 of installation process.
 *
 * @param int $stage The stage
 */

function txp_setup_progress_meter($stage = 1)
{
    $stages = array(
        1 => gTxt('set_db_details'),
        2 => gTxt('add_config_file'),
        3 => gTxt('populate_db'),
        4 => gTxt('get_started'),
    );

    $out = array();

    $out[] = n.'<aside class="progress-meter">'.
        graf(gTxt('progress_steps'), ' class="txp-accessibility"').
        n.'<ol>';

    foreach ($stages as $idx => $phase) {
        $active = ($idx == $stage);
        $sel = $active ? ' class="active"' : '';
        $out[] = n.'<li'.$sel.'>'.($active ? strong($phase) : $phase).'</li>';
    }

    $out[] = n.'</ol>'.
        n.'</aside>';

    return join('', $out);
}

/**
 * Renders stage 1: database details panel.
 */

function step_getDbInfo()
{
    global $cfg;

    echo preamble();
    echo txp_setup_progress_meter(1),
        n.'<div class="txp-setup">';

    check_config_txp2(__FUNCTION__);

    echo '<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        hed(gTxt('need_details'), 1).
        hed('MySQL', 2).
        graf(gTxt('db_must_exist')).
        inputLabel(
            'setup_mysql_login',
            fInput('text', 'duser', @$cfg['mysql']['user'], '', '', '', INPUT_REGULAR, '', 'setup_mysql_login'),
            'mysql_login', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_mysql_pass',
            fInput('password', 'dpass', @$cfg['mysql']['pass'], 'txp-maskable', '', '', INPUT_REGULAR, '', 'setup_mysql_pass').
            n.tag(
                checkbox('unmask', 1, false, 0, 'show_password').
                n.tag(gTxt('setup_show_password'), 'label', array('for' => 'show_password')),
                'div', array('class' => 'show-password')),
            'mysql_password', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_mysql_server',
            fInput('text', 'dhost', (empty($cfg['mysql']['host']) ? 'localhost' : $cfg['mysql']['host']), '', '', '', INPUT_REGULAR, '', 'setup_mysql_server', '', true),
            'mysql_server', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_mysql_db',
            fInput('text', 'ddb', @$cfg['mysql']['db'], '', '', '', INPUT_REGULAR, '', 'setup_mysql_db', '', true),
            'mysql_database', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_table_prefix',
            fInput('text', 'dprefix', @$cfg['mysql']['table_prefix'], 'input-medium', '', '', INPUT_MEDIUM, '', 'setup_table_prefix'),
            'table_prefix', 'table_prefix', array('class' => 'txp-form-field')
        );

    if (is_disabled('mail')) {
        echo msg(gTxt('warn_mail_unavailable'), MSG_ALERT);
    }

    echo graf(
        fInput('submit', 'Submit', gTxt('next_step', '', 'raw'), 'publish')
    );

    echo sInput('step_printConfig').
        n.'</form>'.
        n.'</div>';
}

/**
 * Renders stage 2: either config details panel (on success) or database details
 * error message (on fail).
 */

function step_printConfig()
{
    global $cfg;

    $cfg['mysql']['user'] = ps('duser');
    $cfg['mysql']['pass'] = ps('dpass');
    $cfg['mysql']['host'] = ps('dhost');
    $cfg['mysql']['db'] = ps('ddb');
    $cfg['mysql']['table_prefix'] = ps('dprefix');

    echo preamble();
    echo txp_setup_progress_meter(2).
        n.'<div class="txp-setup">';

    check_config_txp2(__FUNCTION__);

    echo hed(gTxt('checking_database'), 2);

    if (strpos($cfg['mysql']['host'], ':') === false) {
        $dhost = $cfg['mysql']['host'];
        $dport = ini_get("mysqli.default_port");
    } else {
        list($dhost, $dport) = explode(':', $cfg['mysql']['host'], 2);
        $dport = intval($dport);
    }

    $dsocket = ini_get("mysqli.default_socket");

    $mylink = mysqli_init();

    if (@mysqli_real_connect($mylink, $dhost, $cfg['mysql']['user'], $cfg['mysql']['pass'], '', $dport, $dsocket)) {
        $cfg['mysql']['dclient_flags'] = 0;
    } elseif (@mysqli_real_connect($mylink, $dhost, $cfg['mysql']['user'], $cfg['mysql']['pass'], '', $dport, $dsocket, MYSQLI_CLIENT_SSL)) {
        $cfg['mysql']['dclient_flags'] = 'MYSQLI_CLIENT_SSL';
    } else {
        msg(gTxt('db_cant_connect'), MSG_ERROR, __FUNCTION__);
    }

    echo msg(gTxt('db_connected'), MSG_OK);

    if (!($cfg['mysql']['table_prefix'] == '' || preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#', $cfg['mysql']['table_prefix']))) {
        msg(gTxt('prefix_bad_characters',
            array('{dbprefix}' => strong(txpspecialchars($cfg['mysql']['table_prefix']))), 'raw'),
            MSG_ERROR, __FUNCTION__
        );
    }

    if (!$mydb = mysqli_select_db($mylink, $cfg['mysql']['db'])) {
        msg(gTxt('db_doesnt_exist',
            array('{dbname}' => strong(txpspecialchars($cfg['mysql']['db']))), 'raw'),
            MSG_ERROR, __FUNCTION__
        );
    }

    $tables_exist = mysqli_query($mylink, "DESCRIBE `".$cfg['mysql']['table_prefix']."textpattern`");
    if ($tables_exist) {
        msg(gTxt('tables_exist',
            array('{dbname}' => strong(txpspecialchars($cfg['mysql']['db']))), 'raw'),
            MSG_ERROR, __FUNCTION__
        );
    }

    // On MySQL 5.5.3+ use real UTF-8 tables, if the client supports it.
    $cfg['mysql']['dbcharset'] = "utf8mb4";
    // Lower versions only support UTF-8 limited to 3 bytes per character
    if (mysqli_get_server_version($mylink) < 50503) {
        $cfg['mysql']['dbcharset'] = "utf8";
    } else {
        if (false !== strpos(mysqli_get_client_info($mylink), 'mysqlnd')) {
            // mysqlnd 5.0.9+ required
            if (mysqli_get_client_version($mylink) < 50009) {
                $cfg['mysql']['dbcharset'] = "utf8";
            }
        } else {
            // libmysqlclient 5.5.3+ required
            if (mysqli_get_client_version($mylink) < 50503) {
                $cfg['mysql']['dbcharset'] = "utf8";
            }
        }
    }

    echo msg(gTxt('using_db', array(
        '{dbname}' => strong(txpspecialchars($cfg['mysql']['db'])), ), 'raw').' ('.$cfg['mysql']['dbcharset'].')');

    echo setup_config_contents().
        n.'</div>';
}

/**
 * Renders either stage 3: admin user details panel (on success), or stage 2:
 * config details error message (on fail).
 */

function step_getTxpLogin()
{
    global $cfg;

    $problems = array();

    echo preamble();
    check_config_txp(2);

    // Default theme selector.
    $core_themes = array('hive', 'hiveneutral');

    $vals = \Textpattern\Admin\Theme::names(1);

    foreach ($vals as $key => $title) {
        $vals[$key] = (in_array($key, $core_themes) ? gTxt('core_theme', array('{theme}' => $title)) : $title);
    }

    asort($vals, SORT_STRING);

    $theme_chooser = selectInput('theme', $vals, @$cfg['site']['theme'], '', '', 'setup_admin_theme');

    $vals = get_public_themes_list();
    $public_theme_chooser = selectInput('public_theme', $vals, @$cfg['site']['public_theme'], '', '', 'setup_public_theme');

    echo txp_setup_progress_meter(3).
        n.'<div class="txp-setup">'.
        n.'<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        hed(
            gTxt('creating_db_tables'), 2
        ).
        graf(
            gTxt('about_to_create')
        ).
        inputLabel(
            'setup_user_realname',
            fInput('text', 'RealName', @$cfg['user']['realname'], '', '', '', INPUT_REGULAR, '', 'setup_user_realname', '', true),
            'your_full_name', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_user_email',
            fInput('email', 'email', @$cfg['user']['email'], '', '', '', INPUT_REGULAR, '', 'setup_user_email', '', true),
            'your_email', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_user_login',
            fInput('text', 'name', @$cfg['user']['name'], '', '', '', INPUT_REGULAR, '', 'setup_user_login', '', true),
            'setup_login', 'setup_user_login', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_user_pass',
            fInput('password', 'pass', @$cfg['user']['pass'], 'txp-maskable', '', '', INPUT_REGULAR, '', 'setup_user_pass', '', true).
            n.tag(
                checkbox('unmask', 1, false, 0, 'show_password').
                n.tag(gTxt('setup_show_password'), 'label', array('for' => 'show_password')),
                'div', array('class' => 'show-password')),
            'choose_password', 'setup_user_pass', array('class' => 'txp-form-field')
        ).
        hed(
            gTxt('site_config'), 2
        ).
        inputLabel(
            'setup_site_url',
            fInput('text', 'siteurl', @$cfg['site']['siteurl'], '', '', '', INPUT_REGULAR, '', 'setup_site_url', '', true),
            'please_enter_url', 'setup_site_url', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_admin_theme',
            $theme_chooser,
            'admin_theme', 'theme_name', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_public_theme',
            $public_theme_chooser,
            'public_theme', 'public_theme_name', array('class' => 'txp-form-field')
        ).
        graf(
            fInput('submit', 'Submit', gTxt('next_step'), 'publish')
        ).
        sInput('step_createTxp').
        n.'</form>'.
        n.'</div>';
}

/**
 * Re-renders stage 3: admin user details panel, due to user input errors.
 */

function step_createTxp()
{
    global $cfg;

    $cfg['user']['realname'] = ps('RealName');
    $cfg['user']['name'] = ps('name');
    $cfg['user']['pass'] = ps('pass');
    $cfg['user']['email'] = ps('email');

    $cfg['site']['siteurl'] = ps('siteurl');
    $cfg['site']['theme'] = ps('theme');
    $cfg['site']['public_theme'] = ps('public_theme');

    echo preamble();

/*
    if ($_SESSION['name'] == '') {
        echo txp_setup_progress_meter(3).
            n.'<div class="txp-setup">'.
            graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                gTxt('name_required'),
                array('class' => 'alert-block error')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>';

        exit;
    }

    if (!$_SESSION['pass']) {
        echo txp_setup_progress_meter(3).
            n.'<div class="txp-setup">'.
            graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                gTxt('pass_required'),
                array('class' => 'alert-block error')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>';

        exit;
    }

    if (!is_valid_email($_SESSION['email'])) {
        echo txp_setup_progress_meter(3).
            n.'<div class="txp-setup">'.
            graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                gTxt('email_required'),
                array('class' => 'alert-block error')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>';

        exit;
    }
*/

    check_config_txp(3);
    setup_db($cfg);
    echo step_fbCreate();
}

/**
 * Generate a config.php file from the known info.
 */

function makeConfig()
{
    global $cfg;
    define("nl", "';\n");
    define("o", '$txpcfg[\'');
    define("m", "'] = '");
    $open = chr(60).'?php';
    $close = '?'.chr(62);

    // Escape single quotes and backslashes in literal PHP strings.
/*
    foreach ($_SESSION as $k => $v) {
        $_SESSION[$k] = addcslashes($_SESSION[$k], "'\\");
    }

    $_SESSION = doSpecial($_SESSION);
*/
    return
    $open."\n"
    .o.'db'.m.$cfg['mysql']['db'].nl
    .o.'user'.m.$cfg['mysql']['user'].nl
    .o.'pass'.m.$cfg['mysql']['pass'].nl
    .o.'host'.m.$cfg['mysql']['host'].nl
    .(empty($cfg['mysql']['dclient_flags']) ? '' : o.'client_flags'."'] = ".$cfg['mysql']['dclient_flags'].";\n")
    .o.'table_prefix'.m.$cfg['mysql']['table_prefix'].nl
    .o.'txpath'.m.txpath.nl
    .o.'dbcharset'.m.$cfg['mysql']['dbcharset'].nl
    .$close;
}

/**
 * Renders stage 4: either installation completed panel (success) or
 * installation error message (fail).
 */

function step_fbCreate()
{
    global $cfg, $txp_install_fail;

    unset($cfg['mysql']['dclient_flags']);
    unset($cfg['mysql']['dbcharset']);
    $setup_config = "setup_config: <pre>".
        json_encode($cfg, defined('JSON_PRETTY_PRINT') ? TEXTPATTERN_JSON | JSON_PRETTY_PRINT : TEXTPATTERN_JSON).
        "</pre>";

    echo txp_setup_progress_meter(4).
        n.'<div class="txp-setup">';

    if (! empty($txp_install_fail)) {
        // FIXME: some message txp_install_fail
        return msg(gTxt('config_php_not_found', array(
                    '{file}' => ''
                )),
                MSG_ERROR
            ).
            n.'</div>';
    } else {
        // Clear the session so no data is leaked.
        $_SESSION = $cfg = array();

        $warnings = @find_temp_dir() ? '' : msg(gTxt('set_temp_dir_prefs'), MSG_ALERT);

        $login_url = $GLOBALS['rel_txpurl'].'/index.php';

        return hed(gTxt('that_went_well'), 1).
            $warnings.
            graf(
                gTxt('you_can_access', array(
                    'index.php' => $login_url,
                ))
            ).
            graf(
                gTxt('installation_postamble')
            ).
            hed(gTxt('thanks_for_interest'), 3).
            graf(
                href(gTxt('go_to_login'), $login_url, ' class="navlink publish"')
            ).
            $setup_config.
            n.'</div>';
    }
}


/**
 * Populate a textarea with config.php file code.
 *
 * @return HTML
 */

function setup_config_contents()
{
    return hed(gTxt('creating_config'), 2).
        graf(
            strong(gTxt('before_you_proceed')).' '.
            gTxt('create_config', array('{txpath}' => basename(txpath)))
        ).
        n.'<textarea class="code" name="config" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_REGULAR.'" dir="ltr" readonly>'.
            makeConfig().
        n.'</textarea>'.
        n.'<form method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
            graf(fInput('submit', 'submit', gTxt('did_it'), 'publish')).
            sInput('step_getTxpLogin').
        n.'</form>';
}


/**
 * Render a 'back' button that goes to the correct step.
 *
 * @param  string $current The current step in the process
 * @return HTML
 */

function setup_back_button($current = null)
{
    $prevSteps = array(
        'step_getDbInfo'   => '',
        'step_getTxpLogin' => 'step_getDbInfo',
        'step_printConfig' => 'step_getDbInfo',
        'step_createTxp'   => 'step_getTxpLogin',
        'step_fbCreate'    => 'step_createTxp',
    );

    $prev = isset($prevSteps[$current]) ? $prevSteps[$current] : '';

    return graf(gTxt('please_go_back')).
        n.'<form method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        sInput($prev).
        fInput('submit', 'submit', gTxt('back'), 'navlink publish').
        n.'</form>';
}

/**
 * Fetch a dropdown of available languages.
 *
 * The list is fetched from the file system of translations.
 *
 * @return array
 */

function langs()
{
    global $cfg;

    $files = Txp::get('\Textpattern\L10n\Lang')->files();
    $langs = array();

    if (is_array($files) && !empty($files)) {
        foreach ($files as $file) {
            $meta = Txp::get('\Textpattern\L10n\Lang')->fetchMeta($file);
            $langs[$meta['code']] = $meta['name'];
        }
    }

    $out = n.'<div class="txp-form-field">'.
        n.'<div class="txp-form-field-label">'.
        n.'<label for="setup_language">Please choose a language</label>'.
        n.'</div>'.
        n.'<div class="txp-form-field-value">'.
        n.'<select id="setup_language" name="lang">';

    foreach ($langs as $a => $b) {
        $out .= n.'<option value="'.txpspecialchars($a).'"'.
            (($a == $cfg['site']['lang']) ? ' selected="selected"' : '').
            '>'.txpspecialchars($b).'</option>';
    }

    $out .= n.'</select>'.
        n.'</div>'.
        n.'</div>';

    return $out;
}

/**
 * Merge the desired lang strings with fallbacks.
 *
 * The fallback language is guaranteed to exist, so any unknown strings
 * will be used from that pack to fill in any gaps.
 *
 * @param  string $lang The desired language code
 * @return array        The language-specific name-value pairs
 */

function setup_load_lang($lang)
{
    global $language;

    $default_file = Txp::get('\Textpattern\L10n\Lang')->findFilename(TEXTPATTERN_DEFAULT_LANG);
    $default_textpack = array();
    $lang_textpack = array();
    $strings = array();

    // Load the default language strings as fallbacks.
    if ($textpack = @file_get_contents($default_file)) {
        $parser = new \Textpattern\Textpack\Parser();
        $parser->setOwner('');
        $parser->setLanguage(TEXTPATTERN_DEFAULT_LANG);
        $default_textpack = $parser->parse($textpack, 'common, setup');
    }

    $lang_file = Txp::get('\Textpattern\L10n\Lang')->findFilename($lang);

    // Load the desired language strings.
    if ($textpack = @file_get_contents($lang_file)) {
        $parser = new \Textpattern\Textpack\Parser();
        $parser->setOwner('');
        $parser->setLanguage($lang);
        $lang_textpack = $parser->parse($textpack, 'common, setup');
    }

    $language = empty($lang_textpack) ? TEXTPATTERN_DEFAULT_LANG : $lang;
    @define('LANG', $language);

    $allStrings = $lang_textpack + $default_textpack;

    // Merge the arrays, using the default language to fill in the blanks.
    foreach ($allStrings as $meta) {
        if (!array_key_exists($meta['name'], $strings)) {
            $strings[$meta['name']] = $meta['data'];
        }
    }

    return $strings;
}

/**
 * Fetch the list of available public themes.
 *
 * @return array
 */

function get_public_themes_list()
{
    global $public_themes;

    $public_themes = $out = array();

    if ($files = glob(txpath."/{setup/themes,../themes}/*/manifest\.json", GLOB_BRACE)) {
        foreach ($files as $file) {
            $file = realpath($file);

            if (preg_match('%^(.*/(\w+))/manifest\.json$%', $file, $mm) && $manifest = @json_decode(file_get_contents($file), true)) {
                if (@$manifest['txp-type'] == 'textpattern-theme') {
                    $key = $mm[2].'-'.md5($file);
                    $public_themes[$key] = $manifest;
                    $public_themes[$key]['themedir'] = $mm[1];
                    $out[$key] = empty($manifest['title']) ? $mm[2] : $manifest['title']." (".$manifest['version'] .') '.str_replace(txpath, '', $mm[1]);
                }
            }
        }
    }

    return $out;
}


function check_config_txp($meter)
{
    global $txpcfg, $cfg;
    if (!isset($txpcfg['db'])) {
        if (!is_readable(txpath.'/config.php')) {
            $problems[] = msg(gTxt('config_php_not_found', array(
                    '{file}' => txpspecialchars(txpath.'/config.php')
                ), 'raw'), MSG_ERROR);
        } else {
            @include txpath.'/config.php';
        }
    }

    if (!isset($txpcfg)
        || ($txpcfg['db'] != $cfg['mysql']['db'])
        || ($txpcfg['table_prefix'] != $cfg['mysql']['table_prefix'])
    ) {
        $problems[] = msg(gTxt('config_php_does_not_match_input', '', 'raw'), MSG_ERROR);

        echo txp_setup_progress_meter($meter).
            n.'<div class="txp-setup">'.
            n.join(n, $problems).
            setup_config_contents().
            n.'</div>';

        exit;
    }
}


function check_config_txp2($back='')
{
    global $txpcfg;

    if (!isset($txpcfg['db'])) {
        @include txpath.'/config.php';
    }

    if (!empty($txpcfg['db22'])) {
        echo msg(gTxt('already_installed', array('{txpath}' => basename(txpath))), MSG_ALERT, $back);
    }
}





function msg($msg, $class = MSG_OK, $back='')
{
    global $cfg;

    $icon = ($class == MSG_OK) ? 'ui-icon ui-icon-check' : 'ui-icon ui-icon-alert';
    $out = graf(
        span(null, array('class' => $icon)).' '.
        $msg,
        array('class' => $class)
    );

    if (empty($back)) {
        return $out;
    }

    echo $out . setup_back_button($back).n.'</div>';
    $_SESSION['cfg'] = $cfg;
    exit;
}

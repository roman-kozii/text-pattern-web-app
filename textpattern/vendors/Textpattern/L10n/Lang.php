<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
 *
 * Copyright (C) 2016 The Textpattern Development Team
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

/**
 * Language manipulation.
 *
 * @since   4.7.0
 * @package L10n
 */

namespace Textpattern\L10n;

class Lang
{
    /**
     * Language base directory that houses all the language files/textpacks.
     *
     * @var string
     */

    protected $lang_dir = null;

    /**
     * Constructor.
     *
     * @param string $lang_dir Language directory to use
     */

    public function __construct($lang_dir = null)
    {
        if ($lang_dir === null) {
            $lang_dir = txpath.DS.'lang'.DS;
        }

        $this->lang_dir = $lang_dir;
    }

    /**
     * Return all installed languages in the database.
     *
     * @return array Available language codes
     */

    public function installed() {
        static $installed_langs = null;

        if (!$installed_langs) {
            $installed_langs = safe_column("lang", 'txp_lang', "1 = 1 GROUP BY lang");
        }

        return $installed_langs;
    }

    /**
     * Return all language files in the lang directory.
     *
     * @return array Available language filenames
     */

    public function files()
    {
        if (!is_dir($this->lang_dir) || !is_readable($this->lang_dir)) {
            trigger_error('Lang directory is not accessible: '.$this->lang_dir, E_USER_WARNING);

            return array();
        }

        return glob($this->lang_dir.'*.{txt,textpack}', GLOB_BRACE);
    }

    /**
     * Fetch available languages.
     * 
     * Depending on the flags, the returned array can contain active,
     * installed or available language metadata.
     *
     * @return array
     */

    public function available($flags = TEXTPATTERN_LANG_AVAILABLE)
    {
        static $active_lang = null;
        static $in_db = array();
        static $in_fs = array();
        static $allLangs = array();

        if ($active_lang === null) {
            $active_lang = get_pref('language', TEXTPATTERN_DEFAULT_LANG, true);
        }

        if (!$in_db) {
            // We need a value here for the language itself, not for each one of the rows.
            $in_db = safe_rows(
                "lang, UNIX_TIMESTAMP(MAX(lastmod)) AS lastmod",
                'txp_lang',
                "1 = 1 GROUP BY lang ORDER BY lastmod DESC"
            );
        }

        if (!$allLangs) {
            $currently_lang = array();
            $installed_lang = array();
            $available_lang = array();

            foreach ($in_db as $language) {
                if ($language['lang'] === $active_lang) {
                    $currently_lang[$language['lang']] = array(
                        'db_lastmod' => $language['lastmod'],
                        'type'       => 'active',
                    );
                } else {
                    $installed_lang[$language['lang']] = array(
                        'db_lastmod' => $language['lastmod'],
                        'type'       => 'installed',
                    );
                }
            }

            if (!$in_fs) {
                $in_fs = $this->files();
            }

            // Get items from filesystem.
            if (is_array($in_fs) && !empty($in_fs)) {
                $numMetaRows = 5;
                $separator = '=>';

                foreach ($in_fs as $file) {
                    $filename = basename($file);
                    $meta = array();

                    if ($fp = @fopen($file, 'r')) {
                        $name = preg_replace('/\.(txt|textpack)$/i', '', $filename);

                        for ($idx = 0; $idx < $numMetaRows; $idx++) {
                            $meta[] = fgets($fp, 1024);
                        }

                        fclose($fp);

                        $langVersion = $meta[0];
                        $langGroup = trim($meta[1]);
                        $langName = do_list($meta[2], $separator);
                        $langCode = do_list($meta[3], $separator);
                        $langDirection = do_list($meta[4], $separator);

                        $fname = (isset($langName[1])) ? $langName[1] : $name;
                        $fcode = (isset($langCode[1])) ? strtolower($langCode[1]) : $name;
                        $fdirection = (isset($langDirection[1])) ? strtolower($langDirection[1]) : 'ltr';

                        if (strpos($langVersion, '#@version') !== false) {
                            $fversion = trim(substr($langVersion, strpos($langVersion, ' ', 1)));
                            $ftime = filemtime($file);
                        } else {
                            $fversion = $ftime = 0;
                        }

                        if (array_key_exists($name, $currently_lang)) {
                            $currently_lang[$name]['name'] = $fname;
                            $currently_lang[$name]['direction'] = $fdirection;
                            $currently_lang[$name]['file_lastmod'] = $ftime;
                        } elseif (array_key_exists($name, $installed_lang)) {
                            $installed_lang[$name]['name'] = $fname;
                            $installed_lang[$name]['direction'] = $fdirection;
                            $installed_lang[$name]['file_lastmod'] = $ftime;
                        }

                        $available_lang[$name]['file_note'] = $fversion;
                        $available_lang[$name]['file_lastmod'] = $ftime;
                        $available_lang[$name]['name'] = $fname;
                        $available_lang[$name]['direction'] = $fdirection;
                        $available_lang[$name]['type'] = 'available';
                    }
                }
            }

            $allLangs = array(
                'active'    => $currently_lang,
                'installed' => $installed_lang,
                'available' => $available_lang,
            );
        }

        $out = array();

        if ($flags & TEXTPATTERN_LANG_ACTIVE) {
            $out = array_merge($out, $allLangs['active']);
        }

        if ($flags & TEXTPATTERN_LANG_INSTALLED) {
            $out = array_merge($out, $allLangs['installed']);
        }

        if ($flags & TEXTPATTERN_LANG_AVAILABLE) {
            $out = array_merge($out, $allLangs['available']);
        }

        return $out;
    }

    /**
     * Install a language pack from a file.
     *
     * @param  string $lang The lang identifier to load
     */

    public function install_file($lang)
    {
        global $DB;

        $lang_files = glob(txpath.'/lang/'.$lang.'.{txt,textpack}', GLOB_BRACE);

        if (!empty($lang_files)) {
            $lang_file = $lang_files[0];

            if (!is_file($lang_file) || !is_readable($lang_file)) {
                return false;
            }

            $file = fopen($lang_file, "r");

            if ($file) {
                $lastmod = filemtime($lang_file);
                $lastmod = date('YmdHis', $lastmod);
                $data = $core_events = array();
                $event = '';

                // @todo General overhaul:
                //  -> Try $this->install_textpack() to replace this parser.
                //  -> Or use safe_* db functions.
                while (!feof($file)) {
                    $line = fgets($file, 4096);

                    // Ignore empty lines and simple comments (any line starting
                    // with #, not followed by @).
                    if (trim($line) === '' || ($line[0] == '#' && $line[1] != '@' && $line[1] != '#')) {
                        continue;
                    }

                    // If available use the lastmod time from the file.
                    if (strpos($line, '#@version') === 0) {
                        // Looks like: "#@version id;unixtimestamp".
                        @list($fversion, $ftime) = explode(';', trim(substr($line, strpos($line, ' ', 1))));
                        $lastmod = date("YmdHis", min($ftime, time()));
                    }

                    // Each language section should be prefixed by #@.
                    if ($line[0] == '#' && $line[1] == '@') {
                        if (!empty($data)) {
                            foreach ($data as $name => $value) {
                                $value = addslashes($value);
                                $exists = mysqli_query($DB->link, "SELECT name, lastmod FROM `".PFX."txp_lang` WHERE lang = '".$lang."' AND name = '$name' AND event = '$event'");

                                if ($exists) {
                                    $exists = mysqli_fetch_row($exists);
                                }

                                if ($exists[1]) {
                                    mysqli_query($DB->link, "UPDATE `".PFX."txp_lang` SET lastmod = '$lastmod', data = '$value' WHERE owner = '".doSlash(TEXTPATTERN_LANG_OWNER_SYSTEM)."' AND lang = '".$lang."' AND name = '$name' AND event = '$event'");
                                    echo mysqli_error($DB->link);
                                } else {
                                    mysqli_query($DB->link, "INSERT DELAYED INTO `".PFX."txp_lang` SET lang = '".$lang."', name = '$name', lastmod = '$lastmod', event = '$event', data = '$value'");
                                    echo mysqli_error($DB->link);
                                }
                            }
                        }

                        // Reset.
                        $data = array();
                        $event = substr($line, 2, (strlen($line) - 2));
                        $event = rtrim($event);

                        if (strpos($event, 'version') === false) {
                            $core_events[] = $event;
                        }

                        continue;
                    }

                    // Guard against setup strings being loaded.
                    // @todo Setup strings may be removed from the .txt files at some point; this check could then be removed.
                    if ($event !== 'setup') {
                        @list($name, $val) = explode(' => ', trim($line));
                        $data[$name] = $val;
                    }
                }

                // Remember to add the last one.
                if (!empty($data)) {
                    foreach ($data as $name => $value) {
                        mysqli_query($DB->link, "INSERT DELAYED INTO `".PFX."txp_lang` SET lang = '".$lang."', name = '$name', lastmod = '$lastmod', event = '$event', data = '$value'");
                    }
                }

                mysqli_query($DB->link, "DELETE FROM `".PFX."txp_lang` WHERE owner = '' AND lang = '".$lang."' AND event IN ('".join("','", array_unique($core_events))."') AND  lastmod > $lastmod");
                @fclose($file);

                // Delete empty fields if any.
                mysqli_query($DB->link, "DELETE FROM `".PFX."txp_lang` WHERE data = ''");
                mysqli_query($DB->link, "FLUSH TABLE `".PFX."txp_lang`");

                return true;
            }
        }

        return false;
    }

    /**
     * Installs localisation strings from a Textpack.
     *
     * Created strings get a well-known static modification date set in the past.
     * This is done to avoid tampering with lastmod dates used for RPC server
     * interactions, caching and update checks.
     *
     * @param   string $textpack      The Textpack to install
     * @param   bool   $add_new_langs If TRUE, installs strings for any included language
     * @return  int Number of installed strings
     * @package L10n
     */

    public function install_textpack($textpack, $add_new_langs = false)
    {
        $parser = new \Textpattern\Textpack\Parser();
        $parser->setLanguage(get_pref('language', TEXTPATTERN_DEFAULT_LANG));
        $textpack = $parser->parse($textpack);

        if (!$textpack) {
            return 0;
        }

        $installed_langs = $this->installed();
        $done = 0;

        foreach ($textpack as $translation) {
            extract($translation);

            if (!$add_new_langs && !in_array($lang, $installed_langs)) {
                continue;
            }

            $where = "lang = '".doSlash($lang)."' AND name = '".doSlash($name)."'";

            if (safe_count('txp_lang', $where)) {
                $r = safe_update(
                    'txp_lang',
                    "lastmod = '2005-08-14',
                    data = '".doSlash($data)."',
                    event = '".doSlash($event)."',
                    owner = '".doSlash($owner)."'",
                    $where
                );
            } else {
                $r = safe_insert(
                    'txp_lang',
                    "lastmod = '2005-08-14',
                    data = '".doSlash($data)."',
                    event = '".doSlash($event)."',
                    owner = '".doSlash($owner)."',
                    lang = '".doSlash($lang)."',
                    name = '".doSlash($name)."'"
                );
            }

            if ($r) {
                $done++;
            }
        }

        return $done;
    }

    /**
     * Find closest matching language to the given code in the given list.
     *
     * @param  string $lang Language code to match
     * @param  array  $list List of officially supported language codes
     * @return string       Closest matching language identifier
     */
    public function closest($lang, $list)
    {
        $closest = $lang;
        $shortest = PHP_INT_MAX;

        foreach ($list as $currLang) {
            $distance = levenshtein($lang, $currLang);

            if ($distance < $shortest) {
                $shortest = $distance;
                $closest = $currLang;
            }
        }

        return $closest;
    }
}

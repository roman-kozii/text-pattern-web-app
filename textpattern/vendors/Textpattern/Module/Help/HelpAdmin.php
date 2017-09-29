<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
 *
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

/**
 * Help subsystem.
 *
 * @since   4.7.0
 * @package Admin\Help
 */

namespace Textpattern\Module\Help;

class HelpAdmin
{
    private static $available_steps = array(
        'pophelp'   => false,
        'custom'    => false,
        'dashboard' => false,
    );

    private static $textile;
    protected static $pophelp_xml;

    /**
     * Constructor.
     *
     */

    public static function init()
    {
        global $step;
        require_privs('help');

        if ($step && bouncer($step, self::$available_steps)) {
            self::$step();
        } else {
            self::dashboard();
        }
    }


    /**
     * Load pophelp.xml
     *
     * @param string    $lang
     */

    private static function pophelp_load($lang)
    {
        $file = txpath."/lang/{$lang}_pophelp.xml";
        if (!file_exists($file)) {
            return false;
        }
        if (empty(self::$pophelp_xml)) {
            self::$pophelp_xml = simplexml_load_file($file, "SimpleXMLElement", LIBXML_NOCDATA);
        }

        return self::$pophelp_xml;
    }


    /**
     * pophelp.
     */

    public static function pophelp($string = '')
    {
        global $app_mode;

        $item = empty($string) ? gps('item') : $string;
        if (empty($item) || preg_match('/[^\w]/i', $item)) {
            exit;
        }

        $lang_ui = get_pref('language_ui', LANG);

        if (!$xml = self::pophelp_load($lang_ui)) {
            $lang_ui = TEXTPATTERN_DEFAULT_LANG;
            $xml = self::pophelp_load($lang_ui);
        }

        $x = $xml->xpath("//item[@id='{$item}']");
        if (!$x && $lang_ui != TEXTPATTERN_DEFAULT_LANG) {
            $xml = self::pophelp_load(TEXTPATTERN_DEFAULT_LANG);
            $x = $xml->xpath("//item[@id='{$item}']");
        }

        $title = '';
        if ($x) {
            $pophelp = trim($x[0]);
            $title = txpspecialchars($x[0]->attributes()->title);
            $format = $x[0]->attributes()->format;
            if ($format == 'textile') {
                $textile = new \Netcarver\Textile\Parser();
                $out = $textile->textileThis($pophelp).n;
            } else {
                $out = $pophelp.n;
            }
        } else {
            $out = gTxt('help_missing');
        }

        $out = tag($out, 'div', array('id' => 'pophelp-event'));

        if ($app_mode == 'async') {
            pagetop('');
            exit($out);
        }

        return $out;
    }

    public static function dashboard()
    {
        pagetop('dashboard');
        echo <<<EOF
            <h2>Display some minimal Textpattern help from <code>/lang/{current-UI-language}_help.xml</code> or index help files</h2>
            <ul>
                <li><a href="?event=help&step=custom&name=en-gb_pophelp">Test render long page en-gb_pophelp.xml, auto-build TOC</a></li>
            </ul>
EOF;

    }


    public static function custom()
    {
        $name = gps('name');
        if (empty($name) || preg_match('/[^\w\-]/i', $name)) {
            exit;
        }
        $file = txpath."/lang/{$name}.xml";

        pagetop('Custom help');
        if ($data = @file_get_contents($file)) {
            echo hardcode_css_test();
            echo self::render_xml($data, "", "?event=help&step=custom&name={$name}&lang=");
        } else {
            echo "Help file: `{$name}` not found";
        }
    }


    /**
     * render_xml - xml2html
     *
     * @param string        $data   Raw XML
     * @param string/array? $option Allow or not css/js/toc blocks
     * @param string        $href   Link for lang menu
     *
     */

    public static function render_xml($data='', $option='', $hreflang='')
    {
        $out = '';
        if ($xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA)) {
            if (!empty($xml->css)) {
                $out .= '<style type="text/css">'.n.trim($xml->css).n.'</style>'.n;
            }

            // Multilang part, If the file contains more than one language.
            $langs = array();
            foreach ($xml->help as $help) {
                $key = $help->attributes()->lang ? (string)$help->attributes()->lang : 'default';
                $langs[$key] = $help;
            }
            $lang_available = array_keys($langs);

            // detect language
            $lang = gps('lang') ? gps('lang') : get_pref('language_ui', LANG);
            $help = !empty($langs[$lang]) ? $langs[$lang] : array_shift($langs);

            if (count($lang_available) > 1 && !empty($hreflang)) {
                //FIXME: UI, build some language dropdown menu.
                $out .= '<div class="help-menu-lang"><ul>';
                foreach ($lang_available as $lng) {
                    $out .= "<li><a href='{$hreflang}{$lng}'>{$lng}</a></li>";
                }
                $out .= '</ul></div>';
            }


            $menu = array();
            self::$textile = new \Netcarver\Textile\Parser();
            if (!empty($help->toc)) {
                $out .= self::render_item($help->toc, $class='help-toc-static');
            }
            $out2 = '';
            foreach ($help->children() as $key => $children) {
                if ($key == 'group') {
                    $id = (string)$children->attributes()->id;
                    $title = (string)$children->attributes()->title;

                    $out2 .= "<div id='group-{$id}' class='help-group'><h1>{$title}</h1>";
                    $items = array();
                    foreach ($children->item as $item) {
                        $items[] = self::render_item($item);
                    }
                    $out2 .= doWrap($items, '', '')."</div>";
                    $menu[] = tag($title, 'a', array('href' => "#group-{$id}") );
                }
                if ($key == 'item') {
                    $out2 .= self::render_item($children);
                }
            }
            $out .= "<div class='help-toc'>".doWrap($menu, 'ul', 'li')."</div>";

            if (!empty($xml->js)) {
                $out2 .= '<script>'.n.trim($xml->js).n.'</script>'.n;
            }
        }

        return $out.$out2;
    }

    public static function render_item($item , $class='help-item')
    {
        $id = $item->attributes()->id;
        $format = $item->attributes()->format;
        $item = trim($item);
        if ($format == 'textile') {
            $out = self::$textile->textileThis($item);
        } else {
            $out = $item;
        }

//        return tag($out, 'div', array('class' => $class, 'id' => $id));
        return "<div class='{$class}'".(empty($id) ? "" : " id='{$id}'").">$out</div>\n";
    }
}


// Temporary code, it will be deleted.
function hardcode_css_test()
{
    return <<<EOF
<style>
@media screen and (min-width: 500px) {
.help-toc {
    top: 50px;
    position: fixed;
    width: 200px;
}
.help-group {
    margin-right: 200px;

}
}

.help-toc {
    padding-right: 10px;
    right: 0;
    text-align: right;
    border-radius: 50px 0 0 0px;
    box-shadow: -120px 20px 50px #f9f3c0 inset;
    border: 1px solid #f9f3c0;
}
.help-toc a {
    color: #333;
}
.help-group:target > h1 {
    color: red;
    margin-top: 40px;
}

.help-group:target {
    display: block;
}

.help-toc ul {
    list-style-type: none;
}

.help-group {
    border-top: 1px dotted blue;
    border-left: 1px dotted blue;
    padding-left: 10px;
    display: none;
    background-color: #fafafa;
}

.help-item {
    border-bottom: 1px dotted blue;
    margin: 10px;
}
</style>
EOF;
}

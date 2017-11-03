<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
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
 * Skin
 *
 * Manages a skin and its assets — pages, forms and styles by default.
 *
 * <code>
 * Txp::get('Textpattern\Skin\Skin', abc_skin)->import();
 * </code>
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Skin extends SkinBase implements MainInterface
    {

        /**
         * Skin infos as an associative array.
         *
         * @var array
         */

        protected static $table = 'txp_skin';

        /**
         * Skin infos file.
         *
         * @var array
         */

        protected static $file = 'manifest.json';

        /**
         * Default skin assets.
         *
         * @var array
         */

        protected static $assets = array(
            'pages'  => array(),
            'forms'  => array(),
            'styles' => array(),
        );

        /**
         * Constructor.
         *
         * @param string $skin The skin name (set the related parent property);
         */

        public function __construct($skin = null)
        {
            parent::__construct($skin);
        }

        /**
         * Parses the $assets constructor argument.
         *
         * @param string|array $assets See __construct()
         * @return array Associative array of assets and their relative templates.
         */

        private function parseAssets($assets = null)
        {
            if ($assets === null) {
                $assets = static::$assets;
            } elseif ($assets) {
                if (!is_array($assets)) {
                    $assets = array($assets);
                }
                
                if (isset($assets[0])) {
                    $assets = array_fill_keys($assets, array());
                }
            }

            return $assets;
        }

        /**
         * {@inheritdoc}
         */

        public function create($row, $assets = null)
        {
            extract($row);

            $this->skin = $name;

            if (!$this->skinIsInstalled()) {
                $title ?: $title = $name;

                $callback_extra = array(
                    'skin'   => $this->skin,
                    'assets' => $assets,
                );

                callback_event('skin', 'creation', 0, $callback_extra);

                if ($this->doSkin('insert', $name, $title, $version, $description, $author, $author_uri)) {
                    $this->isInstalled = true;

                    $assets = $this->parseAssets($assets);

                    $assets ? $this->callAssetsMethod($assets, __FUNCTION__) : '';

                    callback_event('skin', 'created', 0, $callback_extra);
                } else {
                    callback_event('skin', 'creation_failed', 0, $callback_extra);

                    throw new \Exception(
                        gtxt('skin_step_failure', array(
                            '{skin}' => $this->skin,
                            '{step}' => 'import',
                        ))
                    );
                }
            } else {
                throw new \Exception(
                    gtxt('skin_already_exists', array('{name}' => $this->skin))
                );
            }
        }

        /**
         * {@inheritdoc}
         */

        public function edit($row)
        {
            extract($row);

            if ($this->skinIsInstalled()) {
                if ($this->skin === $name || !self::isInstalled($name)) {
                    $sections = $this->isInUse();
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'assets' => $assets,
                    );

                    callback_event('skin', 'edit', 0, $callback_extra);

                    if ($this->doSkin('update', $name, $title, $version, $description, $author, $author_uri)) {
                        if ($name !== $this->skin) {
                            $path = $this->getPath();

                            if (file_exists($path)) {
                                rename($path, self::getBasePath().'/'.$name);
                            }

                            $old_name = $this->skin;
                            $this->skin = $name;

                            if ($sections) {
                                safe_update(
                                    'txp_section',
                                    "skin = '".doSlash($this->skin)."'",
                                    "skin = '".doSlash($old_name)."'"
                                );
                            }

                            $assets = $this->parseAssets();

                            $assets ? $this->callAssetsMethod($assets, 'adopt', $old_name) : '';
                        }

                        callback_event('skin', 'edited', 0, $callback_extra);
                    } else {
                        callback_event('skin', 'edit_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt('skin_step_failure', array(
                                '{skin}' => $this->skin,
                                '{step}' => 'edit',
                            ))
                        );
                    }
                } else {
                    throw new \Exception(
                        gtxt('skin_already_exists', array('{name}' => $this->skin))
                    );
                }
            } else {
                throw new \Exception(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );
            }
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = true, $assets = null) {
            if (!$this->skinIsInstalled()) {
                if ($this->isReadable(static::$file) && $this->lockSkin()) {
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'assets' => $assets,
                    );

                    callback_event('skin', 'import', 0, $callback_extra);

                    extract($this->getJSONInfos());

                    if ($this->doSkin('insert', $this->skin, $title, $version, $description, $author, $author_uri)) {
                        $this->isInstalled = true;

                        $assets = $this->parseAssets($assets);

                        $assets ? $this->callAssetsMethod($assets, __FUNCTION__, $clean) : '';

                        $this->unlockSkin();

                        callback_event('skin', 'imported', 0, $callback_extra);
                    } else {
                        $this->unlockSkin();

                        callback_event('skin', 'import_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt('skin_step_failure', array(
                                '{skin}' => $this->skin,
                                '{step}' => 'import',
                            ))
                        );
                    }
                }
            } else {
                throw new \Exception(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );
            }
        }

        /**
         * Get and decodes the Manifest file contents.
         *
         * @return array
         * @throws \Exception
         */

        public function getJSONInfos()
        {
            $default = array(
                'title'       => $this->Skin,
                'version'     => '',
                'description' => '',
                'author'      => '',
                'author_uri'  => '',
            );

            $infos = @json_decode(
                file_get_contents($this->getPath(static::$file)),
                true
            );

            if ($infos) {
                return array_merge($default, $infos);
            }

            throw new \Exception(
                gtxt('invalid_json_file', array('{name}' => self::$file))
            );
        }

        /**
         * Inserts or updates a skin row.
         *
         * @param string $method      'insert'|'update';
         * @param string $name        The URL safe skin name;
         * @param string $title       The skin title;
         * @param string $version     The skin version;
         * @param string $description The skin description;
         * @param string $author      The skin author;
         * @param string $author_uri  The skin author URL;
         * @return bool
         */

        public function doSkin($method, $name, $title, $version, $description, $author, $author_uri)
        {
            $set = "name = '".doSlash($name)."',
                    title = '".doSlash($title)."',
                    version = '".doSlash($version)."',
                    description = '".doSlash($description)."',
                    author = '".doSlash($author)."',
                    author_uri = '".doSlash($author_uri)."'
                   ";

            if ($method === 'update') {
                $out = safe_update(self::$table, $set, "name = '".doSlash($this->skin)."'");
            } else {
                $out = safe_insert(self::$table, $set);
            }

            return (bool) $out;
        }

        /**
         * {@inheritdoc}
         */

        public function update($clean = true, $assets = null)
        {
            if ($this->skinIsInstalled()) {
                if ($this->isReadable(static::$file) && $this->lockSkin()) {
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'assets' => $assets,
                    );

                    callback_event('skin', 'update', 0, $callback_extra);

                    extract($this->getJSONInfos());

                    if ($this->doSkin('update', $this->skin, $title, $description, $version, $author, $author_uri)) {
                        $assets = $this->parseAssets($assets);

                        $assets ? $this->callAssetsMethod($assets, __FUNCTION__, $clean) : '';

                        $this->unlockSkin();

                        callback_event('skin', 'updated', 0, $callback_extra);
                    } else {
                        $this->unlockSkin();

                        callback_event('skin', 'update_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt('skin_step_failure', array(
                                '{skin}' => $this->skin,
                                '{step}' => 'update',
                            ))
                        );
                    }
                } else {
                    throw new \Exception(
                        gtxt('unknown_directory', array('{name}' => $this->skin))
                    );
                }
            } else {
                throw new \Exception(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );
            }
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate($assets = null)
        {
            $row = $this->getRow();

            $row['name'] .= '_copy';
            $row['title'] .= ' (copy)';

            if (strlen($row['name']) <= 63) {
                $this->duplicateAs($row);
            } else {
                throw new \Exception(
                    gtxt('skin_name_would_be_too_long', array('{name}' => $name))
                );
            }
        }

        /**
         * {@inheritdoc}
         */

        public function duplicateAs($row, $assets = null)
        {
            extract($row);

            if (!self::isInstalled($name)) {
                $title ?: $title = $name;

                if ($this->skinIsInstalled()) {
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'copy'   => $name,
                        'assets' => $assets,
                    );

                    callback_event('skin', 'duplication', 0, $callback_extra);

                    if ($this->duplicateSkin($name, $title, $version, $description, $author, $author_uri)) {
                        static::$installed[$name] = $name;
                        $assets = $this->parseAssets($assets);

                        $assets ? $this->callAssetsMethod($assets, 'duplicate', $name) : '';

                        callback_event('skin', 'duplicated', 0, $callback_extra);
                    } else {
                        callback_event('skin', 'duplication_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt('skin_step_failure', array(
                                '{skin}' => $this->skin,
                                '{step}' => 'duplication',
                            ))
                        );
                    }
                } else {
                    throw new \Exception(
                        gtxt('unknown_skin', array('{name}' => $this->skin))
                    );
                }
            } else {
                throw new \Exception(
                    gtxt('skin_already_exists', array('{name}' => $name))
                );
            }
        }

        /**
         * Gets the skin row from the database.
         *
         * @throws \Exception
         */

        public function getRow()
        {
            $row = safe_row('*', self::$table, 'name = "'.doSlash($this->skin).'"');

            if ($row) {
                return $row;
            }

            throw new \Exception(
                gtxt('skin_data_not_found', array('{name}' => $this->skin))
            );
        }

        /**
         * Duplicates the skin row.
         *
         * @param string $name        The skin copy name;
         * @param string $title       The skin copy title;
         * @param string $version     The skin copy version;
         * @param string $description The skin copy description;
         * @param string $author      The skin copy author;
         * @param string $author_uri  The skin copy author URL;
         * @return bool
         */

        public function duplicateSkin(
            $name,
            $title,
            $version,
            $description,
            $author,
            $author_uri
        ) {
            return (bool) safe_insert(
                self::$table,
                "title = '".doSlash($title)."',
                 version = '".doSlash($version)."',
                 description = '".doSlash($description)."',
                 author = '".doSlash($author)."',
                 author_uri = '".doSlash($author_uri)."',
                 name = '".doSlash($name)."'
                "
            );
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true, $assets = null)
        {
            $row = $this->getRow();

            if ($row) {
                $callback_extra = array(
                    'skin'   => $this->skin,
                    'assets' => $assets,
                );

                callback_event('skin', 'export', 0, $callback_extra);

                if (($this->isWritable() || $this->mkDir()) && $this->lockSkin()) {
                    if ($this->exportSkin($row)) {
                        $assets = $this->parseAssets($assets);

                        $assets ? $this->callAssetsMethod($assets, __FUNCTION__, $clean) : '';

                        $this->unlockSkin();

                        callback_event('skin', 'exported', 0, $callback_extra);
                    } else {
                        $this->unlockSkin();

                        callback_event('skin', 'export_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt('skin_step_failure', array(
                                '{skin}' => $this->skin,
                                '{step}' => 'export',
                            ))
                        );
                    }
                }
            } else {
                throw new \Exception(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );
            }
        }

        /**
         * Exports the skin row by creating or editing the Manifest file contents.
         *
         * @param  array $row Skin row as an associative array
         * @return bool  False on error
         */

        public function exportSkin($row)
        {
            extract($row);

            $contents = $this->isWritable(static::$file) ? $this->getJSONInfos() : array();

            $contents['title'] = $title ? $title : $name;
            $contents['txp-type'] = 'textpattern-theme';
            $version ? $contents['version'] = $version : '';
            $description ? $contents['description'] = $description : '';
            $author ? $contents['author'] = $author : '';
            $author_uri ? $contents['author_uri'] = $author_uri : '';

            return (bool) $this->filePutJsonContents($contents);
        }

        /**
         * Creates/overrides the Manifest file.
         *
         * @param  array $contents The manifest file contents;
         * @return bool  False on error.
         */

        public function filePutJsonContents($contents)
        {
            return (bool) file_put_contents(
                $this->getPath(static::$file),
                $this->JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON))
            );
        }

        /**
         * Replaces the JSON_PRETTY_PRINT flag in json_encode for PHP versions under 5.4.
         *
         * From https://stackoverflow.com/a/9776726
         *
         * @param  string $json The JSON contents to prettify;
         * @return string Prettified JSON contents.
         */

        public function JSONPrettyPrint($json)
        {
            $result = '';
            $level = 0;
            $in_quotes = false;
            $in_escape = false;
            $ends_line_level = null;
            $json_length = strlen($json);

            for ($i = 0; $i < $json_length; $i++) {
                $char = $json[$i];
                $new_line_level = null;
                $post = "";

                if ($ends_line_level !== null) {
                    $new_line_level = $ends_line_level;
                    $ends_line_level = null;
                }

                if ($in_escape) {
                    $in_escape = false;
                } elseif ($char === '"') {
                    $in_quotes = !$in_quotes;
                } elseif (! $in_quotes) {
                    switch ($char) {
                        case '}':
                        case ']':
                            $level--;
                            $ends_line_level = null;
                            $new_line_level = $level;
                            break;
                        case '{':
                        case '[':
                            $level++;
                        case ',':
                            $ends_line_level = $level;
                            break;
                        case ':':
                            $post = " ";
                            break;
                        case " ":
                        case "    ":
                        case "\n":
                        case "\r":
                            $char = "";
                            $ends_line_level = $new_line_level;
                            $new_line_level = null;
                            break;
                    }
                } elseif ($char === '\\') {
                    $in_escape = true;
                }

                if ($new_line_level !== null) {
                    $result .= "\n".str_repeat("    ", $new_line_level);
                }

                $result .= $char.$post;
            }

            return $result;
        }

        /**
         * {@inheritdoc}
         */

        public function delete()
        {
            if (!$this->skinIsInstalled()) {
                throw new \Exception(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );
            } elseif ($this->isInUse()) {
                throw new \Exception(
                    gtxt('skin_delete_failure', array(
                        '{name}' => $this->skin,
                        '{clue}' => 'skin in use.',
                    ))
                );
            } elseif (count(Main::getInstalled()) < 1) {
                throw new \Exception(
                    gtxt('skin_delete_failure', array(
                        '{name}' => $this->skin,
                        '{clue}' => 'last skin.',
                    ))
                );
            } else {
                $callback_extra = array(
                    'skin'   => $this->skin,
                    'assets' => $assets,
                );

                callback_event('skin', 'deletion', 0, $callback_extra);

                $assets = $this->parseAssets($assets);

                $assets ? $this->callAssetsMethod($assets, __FUNCTION__) : '';

                if ($this->hasAssets()) {
                    throw new \Exception(
                        gtxt('skin_delete_failure', array(
                            '{name}' => $this->skin,
                            '{clue}' => 'still contains assets.',
                        ))
                    );
                } elseif ($this->deleteSkin()) {
                    static::$installed = array_diff_key(
                        static::$installed,
                        array($this->skin => '')
                    );

                    self::getCurrent() === $this->skin ? self::setCurrent($this->skin) : '';

                    callback_event('skin', 'deleted', 0, $callback_extra);
                } else {
                    callback_event('skin', 'deletion_failed', 0, $callback_extra);

                    throw new \Exception(
                        gtxt('skin_step_failure', array(
                            '{skin}' => $this->skin,
                            '{step}' => 'deletion',
                        ))
                    );
                }
            }
        }

        /**
         * Deletes the skin row.
         *
         * @return bool  False on error.
         */

        public function deleteSkin()
        {
            return (bool) safe_delete(
                doSlash(self::$table),
                "name = '".doSlash($this->skin)."'"
            );
        }

        /**
         * {@inheritdoc}
         */

        public function isInUse()
        {
            if ($this->skin) {
                if ($this->isInUse === null) {
                    $this->isInUse = safe_column(
                        "name",
                        'txp_section',
                        "skin ='".doSlash($this->skin)."'"
                    );
                }

                return $this->isInUse;
            }

            throw new \Exception('undefined_skin');
        }

        /**
         * Checks if any template belongs to the skin.
         *
         * @return bool
         * @throws \Exception
         */

        public function hasAssets()
        {
            if ($this->skin) {
                $assets = safe_query(
                    'SELECT p.name, f.name, c.name
                     FROM '.safe_pfx('txp_page').' p,
                          '.safe_pfx('txp_form').' f,
                          '.safe_pfx('txp_css').' c
                     WHERE p.skin = "'.doSlash($this->skin).'" AND
                           f.skin = "'.doSlash($this->skin).'" AND
                           c.skin = "'.doSlash($this->skin).'"
                    '
                );

                return @mysqli_num_rows($assets) > 0 ? true : false;
            }

            throw new \Exception('undefined_skin');
        }

        /**
         * Gets the skin set as the one selected in the admin tabs.
         *
         * @return string The skin name
         */

        public static function getCurrent()
        {
            return get_pref('skin_editing', 'default');
        }

        /**
         * Sets the skin as the one selected in the admin tabs.
         *
         * @param  string $skin A skin name.
         * @throws \Exception
         */

        public static function setCurrent($skin = null)
        {
            $skin ?: $skin = safe_field('skin', 'txp_section', 'name = "default"');

            if (set_pref('skin_editing', $skin, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE) === false) {
                throw new \Exception('unable_to_set_current_skin');
            }
        }

        /**
         * Calls an asset class instance method.
         *
         * @param string $method An asset class related method;
         * @param array  $args   An array to pass to the method;
         * @throws \Exception
         */

        private function callAssetsMethod($assets, $method, $extra = null)
        {
            $exceptions = array();

            foreach ($assets as $asset => $templates) {
                try {
                    $instance = \Txp::get('Textpattern\Skin\\'.ucfirst($asset), $this->skin);
                    $instance->locked = $this->locked;

                    if ($extra) {
                        call_user_func_array(
                            array($instance, $method),
                            array($extra, $templates)
                        );
                    } else {
                        call_user_func_array(
                            array($instance, $method),
                            array($templates)
                        );
                    }
                } catch (\Exception $e) {
                    $exceptions[] = $e->getMessage();
                }
            }

            if ($exceptions) {
                $this->locked ? $this->unlockSkin() : '';

                foreach ($assets as $asset => $templates) {
                    $instance = \Txp::get('Textpattern\Skin\\'.ucfirst($asset), $this->skin);
                    $instance->locked = $this->locked;
                }

                throw new \Exception(implode(n, $exceptions));
            }
        }
    }
}

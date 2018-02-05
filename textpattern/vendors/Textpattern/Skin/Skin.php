<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
 * SharedBase
 *
 * Extended by Main and AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Skin extends Base
    {
        /**
         * Skin assets related objects.
         *
         * @var array Page, Form and CSS class objects.
         * @see       setAssets().
         */

        private $assets;

        /**
         * {@inheritdoc}
         */

        protected static $table = 'txp_skin';

        /**
         * {@inheritdoc}
         */

        protected static $event = 'skin';

        /**
         * Class related main file.
         *
         * @var string Filename.
         * @see        getFile().
         */

        protected static $filename = 'manifest.json';

        /**
         * Importable skins.
         *
         * @var array Associative array of skin names and their titles
         * @see       setUploaded(), getUploaded().
         */

        protected static $uploaded;

        /**
         * Class related directory path.
         *
         * @var string Path.
         * @see        setDirPath(), getDirPath().
         */

        protected $dirPath;

        /**
         * {@inheritdoc}
         */

        public function __construct()
        {
            $this->setName();
        }

        protected function mergeResults($asset, $status) {
            $thisResults = $this->getResults();
            $this->results = array_merge($thisResults, $asset->getResults($status));

            return $this;
        }

        /**
         * $dirPath property setter.
         *
         * @param  string $path          Path (default: get_pref('path_to_site').DS.get_pref('skin_dir')).
         * @return string $this->dirPath
         */

        public function setDirPath($path = null)
        {
            $path !== null ?: $path = get_pref('path_to_site').DS.get_pref(self::getEvent().'_dir');

            $this->dirPath = rtrim($path, DS);

            return $this->getDirPath();
        }

        /**
         * $dirPath property getter
         *
         * @return string $this->dirPath
         */

        protected function getDirPath()
        {
            $this->dirPath !== null ?: $this->setDirPath();

            return $this->dirPath;
        }

        /**
         * $assets property setter.
         *
         * @param array   $pages  Page names to work with;
         * @param array   $forms  Form names to work with;
         * @param array   $styles CSS names to work with.
         * @return object $this
         */

        public function setAssets($pages = null, $forms = null, $styles = null)
        {
            $assets = array(
                'Page' => $pages,
                'Form' => $forms,
                'CSS'  => $styles,
            );

            foreach ($assets as $class => $assets) {
                $this->assets[] = \Txp::get('Textpattern\Skin\\'.$class, $this)->setNames($assets);
            }

            return $this;
        }

        /**
         * $assets property getter.
         *
         * @return array $this->$assets
         */

        protected function getAssets()
        {
            $this->assets !== null ?: $this->setAssets();

            return $this->assets;
        }

        /**
         * {@inheritdoc}
         */

        protected static function sanitize($name)
        {
            return sanitizeForTheme($name);
        }

        /**
         * $infos and $name properties setter.
         *
         * @param  string $name        Skin name;
         * @param  string $title       Skin title;
         * @param  string $version     Skin version;
         * @param  string $description Skin description;
         * @param  string $author      Skin author;
         * @param  string $author_uri  Skin author URL;
         * @return object $this
         */

        public function setInfos(
            $name,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null
        ) {
            $this->setName($name);

            $name = $this->getName();

            $title ?: $title = ucfirst($name);

            $this->infos = compact('name', 'title', 'version', 'description', 'author', 'author_uri');

            return $this;
        }

        /**
         * Whether a skin is installed or not.
         *
         * @param  string $name Skin name (default: $this->getName()).
         * @return bool
         */

        public function isInstalled($name = null)
        {
            $name !== null ?: $name = $this->getName();

            if (self::$installed === null) {
                $isInstalled = (bool) $this->getField('name', "name = '".$name."'");
            } else {
                $isInstalled = in_array($name, array_keys($this->getInstalled()));
            }

            return $isInstalled;
        }

        /**
         * Get a $dir property value related subdirectory path.
         *
         * @param string  $name Directory(/skin) name (default: $this->getName()).
         * @return string       The Path
         */

        public function getSubdirPath($name = null)
        {
            $name !== null ?: $name = $this->getName();

            return $this->getDirPath().DS.$name;
        }

        /**
         * $file property getter.
         *
         * @return string self::$filename.
         */

        protected static function getFilename()
        {
            return self::$filename;
        }

        /**
         * Get the $file property value related path.
         *
         * @return string Path.
         */

        protected function getFilePath()
        {
            return $this->getSubdirPath().DS.self::getFilename();
        }

        /**
         * Get and complete the skin related file contents.
         *
         * @return array Associative array of JSON fields and their related values / fallback values.
         */

        protected function getFileContents()
        {
            $contents = json_decode(file_get_contents($this->getFilePath()), true);

            if ($contents !== null) {
                extract($contents);

                !empty($title) ?: $title = ucfirst($this->getName());
                !empty($version) ?: $version = gTxt('unknown');
                !empty($description) ?: $description = '';
                !empty($author) ?: $author = gTxt('unknown');
                !empty($author_uri) ?: $author_uri = '';

                $contents = compact('title', 'version', 'description', 'author', 'author_uri');
            }

            return $contents;
        }

        /**
         * $sections property getter.
         *
         * @param array Section names.
         */

        protected function getSections()
        {
            return array_values(
                safe_column(
                    'name',
                    'txp_section',
                    "skin ='".doSlash($this->getName())."'"
                )
            );
        }

        /**
         * Update the txp_section table.
         *
         * @param  string $set   The SET clause (default: "skin = '".doSlash($this->getName())."'")
         * @param  string $where The WHERE clause (default: "skin = '".doSlash($this->getBase())."'")
         * @return bool          FALSE on error.
         */

        protected function updateSections($set = null, $where = null)
        {
            $set !== null ?: $set = "skin = '".doSlash($this->getName())."'";
            $where !== null ?: $where = "skin = '".doSlash($this->getBase())."'";

            return safe_update('txp_section', $set, $where);
        }

        /**
         * {@inheritdoc}
         */

        public static function getEditing()
        {
            return get_pref(self::getEvent().'_editing', 'default', true);
        }

        /**
         * {@inheritdoc}
         */

        public function setEditing($name = null)
        {
            global $prefs;

            $event = self::getEvent();

            $name !== null ?: $name = $this->getName();
            $prefs[$event.'_editing'] = $name;

            set_pref($event.'_editing', $name, $event, PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);

            return self::getEditing();
        }

        /**
         * Get the skin name used by the default section.
         *
         * @return mixed Skin name or FALSE on error.
         */

        public static function getDefault()
        {
            return safe_field(self::getEvent(), 'txp_section', 'name = "default"');
        }

        /**
         * Create a file in the $dir property value related directory.
         *
         * @param  string $pathname The file related path (default: $this->getName().DS.self::getFilename()).
         * @param  mixed  $contents The file related contents as as a string or
         *                          as an associative array for a .json file
         *                          (uses the $infos property related array).
         * @return bool             Written octets number or FALSE on error.
         */

        protected function createFile($pathname = null, $contents = null) {
            $pathname !== null ?: $pathname = $this->getName().DS.self::getFilename();

            if ($contents === null) {
                $contents = array_merge(
                    $this->getInfos(),
                    array('txp-type' => 'textpattern-theme')
                );

                unset($contents['name']);
            }

            if (pathinfo($pathname, PATHINFO_EXTENSION) === 'json') {
                $contents = JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON));
            }

            return file_put_contents($this->getDirPath().DS.$pathname, $contents);
        }

        /**
         * $uploaded property setter.
         *
         * @return object $this.
         */

        protected function setUploaded()
        {
            self::$uploaded = array();
            $files = $this->getFiles(array(self::getFilename()), 1);

            if ($files) {
                foreach ($files as $file) {
                    $filePath = $file->getPath();
                    $name = basename($file->getPath());

                    if ($name === self::sanitize($name)) {
                        $infos = $file->getJSONContents();
                        !$infos ?: self::$uploaded[$name] = $infos['title'];
                    }
                }
            }

            return $this->getUploaded();
        }

        /**
         * $uploaded property getter.
         *
         * @return array self::$uploaded.
         */

        protected function getUploaded()
        {
            return self::$uploaded === null ? $this->setUploaded() : self::$uploaded;
        }

        /**
         * $installed property merger.
         *
         * @param array self::$installed.
         */

        protected function mergeInstalled($skins)
        {
            self::$installed = array_merge($this->getInstalled(), $skins);

            return $this->getInstalled();
        }

        /**
         * $installed property remover.
         *
         * @return array self::$installed.
         */

        protected function removeInstalled($names)
        {
            self::$installed = array_diff_key(
                $this->getInstalled(),
                array_fill_keys($names, '')
            );

            return $this->getInstalled();
        }

        /**
         * {@inheritdoc}
         */

        protected function getTableData($criteria, $sortSQL, $offset, $limit)
        {
            $assets = array('section', 'page', 'form', 'css');
            $things = array('*');

            foreach ($assets as $asset) {
                $things[] = '(SELECT COUNT(*) '
                            .'FROM '.safe_pfx_j('txp_'.$asset).' '
                            .'WHERE txp_'.$asset.'.skin = txp_skin.name) '
                            .$asset.'_count';
            }

            return safe_rows_start(
                implode(', ', $things),
                'txp_skin',
                $criteria.' order by '.$sortSQL.' limit '.$offset.', '.$limit
            );
        }

        /**
         * Create/CreateFrom a single skin (and its related assets)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function create() {
            $infos = $this->getInfos();
            $name = $infos['name'];
            $base = $this->getBase();
            $event = self::getEvent();

            callback_event($event.'.create', '', 1, compact('infos', 'base'));

            $done = false;

            if (empty($name)) {
                $this->mergeResult($event.'_name_invalid', $name);
            } elseif ($base && !$this->isInstalled($base)) {
                $this->mergeResult($event.'_unknown', $base);
            } elseif ($this->isInstalled()) {
                $this->mergeResult($event.'_already_exists', $name);
            } elseif (is_dir($subdirPath = $this->getSubdirPath())) {
                // Create a skin which would already have a related directory could cause conflicts.
                $this->mergeResult($event.'_already_exists', $subdirPath);
            } elseif (!$this->createRow()) {
                $this->mergeResult($event.'_creation_failed', $name);
            } else {
                $this->mergeResult($event.'_created', $name, 'success');

                // Start working with the skin related assets.
                foreach ($this->getAssets() as $assetModel) {
                    if ($base) {
                        $this->setName($base);
                        $rows = $assetModel->getRows();
                        $this->setName($name);
                    } else {
                        $rows = null;
                    }

                    if (!$assetModel->createRows($rows)) {
                        $assetsfailed = true;

                        $this->mergeResult($assetModel->getEvent().'_creation_failed', $name);
                    }
                }

                // If the assets related process did not failed; that is a success…
                isset($assetsfailed) ?: $done = $name;
            }

            callback_event($event.'.create', '', 0, compact('infos', 'base', 'done'));

            return $this; // Chainable.
        }

        /**
         * Update a single skin (and its related dependencies)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function update() {
            $infos = $this->getInfos();
            $name = $infos['name'];
            $base = $this->getBase();
            $event = self::getEvent();

            callback_event($event.'.update', '', 1, compact('infos', 'base'));

            $done = null; // See the final callback event.
            $ready = false;

            if (empty($name)) {
                $this->mergeResult($event.'_name_invalid', $name);
            } elseif (!$this->isInstalled($base)) {
                $this->mergeResult($event.'_unknown', $base);
            } elseif ($base !== $name && $this->isInstalled()) {
                $this->mergeResult($event.'_already_exists', $name);
            } elseif (is_dir($subdirPath = $this->getSubdirPath()) && $base !== $name) {
                // Rename the skin with a name which would already have a related directory could cause conflicts.
                $this->mergeResult($event.'_already_exists', $subdirPath);
            } elseif (!$this->updateRow()) {
                $this->mergeResult($event.'_update_failed', $base);
                $locked = $base;
            } else {
                $this->mergeResult($event.'_updated', $name, 'success');
                $ready = true;
                $locked = $base;

                // Rename the skin related directory to allow new updates from files.
                if (is_dir($this->getSubdirPath($base)) && !@rename($this->getSubdirPath($base), $subdirPath)) {
                    $this->mergeResult('path_renaming_failed', $base, 'warning');
                } else {
                    $locked = $name;
                }
            }

            if ($ready) {
                // Update skin related sections.
                $sections = $this->getSections();

                if ($sections && !$this->updateSections()) {
                    $this->mergeResult($event.'_related_sections_update_failed', array($base => $sections));
                }

                // update the skin_editing pref if needed.
                self::getEditing() !== $base ?: $this->setEditing();

                // Start working with the skin related assets.
                foreach ($this->getAssets() as $assetModel) {
                    if (!$assetModel->updateRow($event." = '".doSlash($this->getName())."'", $event." = '".doSlash($this->getBase())."'")) {
                        $assetsFailed = true;
                        $this->mergeResult($assetModel->getEvent().'_update_failed', $base);
                    }
                }

                // If the assets related process did not failed; that is a success…
                isset($assetsFailed) ?: $done = $name;
            }

            callback_event($event.'.update', '', 0, compact('infos', 'base', 'done'));

            return $this; // Chainable
        }

        /**
         * Duplicate multiple skins (and their related $assets)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function duplicate()
        {
            $names = $this->getNames();
            $event = self::getEvent();

            callback_event($event.'.duplicate', '', 1, compact('names'));

            $ready = $done = array(); // See the final callback event.

            foreach ($names as $name) {
                $subdirPath = $this->setName($name)->getSubdirPath();
                $copy = $name.'_copy';

                if (!$this->isInstalled()) {
                    $this->mergeResult($event.'_unknown', $name);
                } elseif ($this->isInstalled($copy)) {
                    $this->mergeResult($event.'_already_exists', $copy);
                } elseif (is_dir($copyPath = $this->getSubdirPath($copy))) {
                    $this->mergeResult($event.'_already_exists', $copyPath);
                } else {
                    $ready[] = $name;
                }
            }

            if ($ready) {
                $rows = $this->getRows(null, "name IN ('".implode("', '", array_map('doSlash', $ready))."')"); // Get all skin rows at once.

                if (!$rows) {
                    $this->mergeResult($event.'_duplication_failed', $name);
                } else {
                    foreach ($rows as $row) {
                        extract($row);

                        $copy = $name.'_copy';
                        $copyTitle = $title.' (copy)';

                        if (!$this->setInfos($copy, $copyTitle, $version, $description, $author, $author_uri)->createRow()) {
                            $this->mergeResult($event.'_duplication_failed', $name);
                        } else {
                            $this->mergeResult($event.'_duplicated', $name, 'success');
                            $this->mergeInstalled(array($copy => $copyTitle));

                            // Start working with the skin related assets.
                            foreach ($this->getAssets() as $assetModel) {
                                $this->setName($name);
                                $assetString = $assetModel::getEvent();
                                $assetRows = $assetModel->getRows();

                                if (!$assetRows) {
                                    $deleteExtraFiles = true;

                                    $this->mergeResult($assetString.'_not_found', array($skin => $subdirPath));
                                } elseif ($this->setName($copy) && !$assetModel->createRows($assetRows)) {
                                    $deleteExtraFiles = true;

                                    $this->mergeResult($assetString.'_duplication_failed', array($skin => $notImported));
                                }
                            }

                            $this->setName($name); // Be sure to restore the right $name.

                            // If the assets related process did not failed; that is a success…
                            isset($deleteExtraFiles) ?: $done[] = $name;
                        }
                    }
                }
            }

            callback_event($event.'.duplicate', '', 0, compact('names', 'done'));

            return $this; // Chainable
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = false, $override = false)
        {
            $clean == $this->getCleaningPref() ?: $this->switchCleaningPref();
            $names = $this->getNames();
            $event = self::getEvent();

            callback_event($event.'.import', '', 1, compact('names'));

            $done = array(); // See the final callback event.

            foreach ($names as $name) {
                $this->setName($name);

                $isInstalled = $this->isInstalled();
                $isInstalled ?: $clean = $override = false; // Avoid useless work.

                if (!$override && $isInstalled) {
                    $this->mergeResult($event.'_already_exists', $name);
                } elseif (!is_readable($filePath = $this->getFilePath())) {
                    $this->mergeResult('path_not_readable', $filePath);
                } else {
                    $skinInfos = array_merge(array('name' => $name), $this->getFileContents());

                    if (!$skinInfos) {
                        $this->mergeResult('invalid_json', $filePath);
                    } else {
                        extract($skinInfos);

                        $this->setInfos($name, $title, $version, $description, $author, $author_uri);

                        if (!$override && !$this->createRow()) {
                            $this->mergeResult($event.'_import_failed', $name);
                        } elseif ($override && !$this->updateRow(null, "name = '".doSlash($this->getBase())."'")) {
                            $this->mergeResult($event.'_import_failed', $name);
                        } else {
                            $this->mergeResult($event.'_imported', $name, 'success');
                            $this->mergeInstalled(array($name => $title));

                            // Start working with the skin related assets.
                            foreach ($this->getAssets() as $asset) {
                                $asset->import($clean, $override);

                                if (is_array($asset->getMessage())) {
                                    $assetFailed = true;

                                    $this->mergeResults($asset, array('warning', 'error'));
                                }
                            }
                        }

                        // If the assets related process did not failed; that is a success…
                        isset($assetFailed) ?: $done[] = $name;
                    }
                }
            }

            callback_event($event.'.import', '', 0, compact('names', 'done'));

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = false, $override = false)
        {
            $clean == $this->getCleaningPref() ?: $this->switchCleaningPref();

            $names = $this->getNames();
            $event = self::getEvent();

            callback_event($event.'.export', '', 1, compact('names'));

            $ready = $done = array();

            foreach ($names as $name) {
                $this->setName($name);

                $subdirPath = $this->getSubdirPath();

                if (!is_writable($subdirPath)) {
                    $clean = false;
                    $override = false;
                }

                if (!self::isValidDirName($name)) {
                    $this->mergeResult($event.'_unsafe_name', $name);
                } elseif (!$override && is_dir($subdirPath)) {
                    $this->mergeResult($event.'_already_exists', $subdirPath);
                } elseif (!is_dir($subdirPath) && !@mkdir($subdirPath)) {
                    $this->mergeResult('path_not_writable', $subdirPath);
                } else {
                    $ready[] = $name;
                }
            }

            if ($ready) {
                $rows = $this->getRows(null, "name IN ('".implode("', '", array_map('doSlash', $ready))."')");

                if (!$rows) {
                    $this->mergeResult($event.'_unknown', $names);
                } else {
                    foreach ($rows as $row) {
                        extract($row);

                        $this->setName($name);

                        if ($this->setInfos($name, $title, $version, $description, $author, $author_uri)->createFile() === false) {
                            $this->mergeResult($event.'_export_failed', $name);
                        } else {
                            $this->mergeResult($event.'_exported', $name, 'success');

                            foreach ($this->getAssets() as $asset) {
                                $asset->export($clean, $override);

                                if (is_array($asset->getMessage())) {
                                    $assetFailed = true;

                                    $this->mergeResults($asset, array('warning', 'error'));
                                }
                            }

                            isset($assetFailed) ?: $done[] = $name;
                        }
                    }
                }
            }

            callback_event($event.'.export', '', 0, compact('names', 'done'));

            return $this;
        }

        /**
         * Delete multiple skins (and their related $assets + directories if empty)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function delete($clean = false)
        {
            $names = $this->getNames();
            $event = self::getEvent();

            callback_event($event.'.delete', '', 1, compact('names'));

            $ready = $done = array();

            foreach ($names as $name) {
                if (!$this->setName($name)->isInstalled()) {
                    $this->mergeResult($event.'_unknown', $name);
                } elseif ($sections = $this->getSections()) {
                    $this->mergeResult($event.'_in_use', array($name => $sections));
                } else {
                    /**
                     * Start working with the skin related assets.
                     * Done first as assets won't be accessible
                     * once their parent skin will be deleted.
                     */
                    $assetFailed = false;

                    foreach ($this->getAssets() as $assetModel) {
                        if (!$assetModel->deleteRows()) {
                            $this->mergeResult($assetModel->getEvent().'_deletion_failed', $name);
                        }
                    }

                    $assetFailed ?: $ready[] = $name;
                }
            }

            if ($ready) {
                if ($this->deleteRows("name IN ('".implode("', '", array_map('doSlash', $ready))."')")) {
                    $done = $ready;

                    $this->removeInstalled($ready);

                    if (in_array(self::getEditing(), $ready)) {
                        $default = self::getDefault();

                        !$default ?: $this->setEditing($default);
                    }

                    $this->mergeResult($event.'_deleted', $ready, 'success');

                    // Remove all skins files and directories if needed.
                    if ($clean) {
                        foreach ($ready as $name) {
                            if (is_dir($this->getSubdirPath($name)) && !$this->deleteFiles(array($name))) {
                                $this->mergeResult($event.'_files_deletion_failed', $name);
                            }
                        }
                    }

                    update_lastmod($event.'.delete', $ready);
                } else {
                    $this->mergeResult($event.'_deletion_failed', $ready);
                }
            }

            callback_event($event.'.delete', '', 0, compact('names', 'done'));

            return $this;
        }

        /**
         * Delete Files from the $dir property value related dirstory.
         *
         * @param  string $names directory/file names.
         * @return bool   0 on error.
         */

        protected function deleteFiles($names = null)
        {
            return \Txp::get('Textpattern\Admin\Tools')::removeFiles($this->getDirPath(), $names);
        }

        /**
         * Control the admin tab.
         */

        public function admin()
        {
            if (!defined('txpinterface')) {
                die('txpinterface is undefined.');
            }

            global $event, $step;

            if ($event === self::getEvent()) {
                require_privs($event);

                bouncer($step, array(
                    $event.'_change_pageby' => true, // Prefixed to make it work with the paginator…
                    'list'          => false,
                    'edit'          => false,
                    'save'          => true,
                    'import'        => false,
                    'multi_edit'    => true,
                ));

                switch ($step) {
                    case 'save':
                        $infos = array_map('assert_string', psa(array(
                            'name',
                            'title',
                            'old_name',
                            'old_title',
                            'version',
                            'description',
                            'author',
                            'author_uri',
                            'copy',
                        )));

                        extract($infos);

                        if ($old_name) {
                            if ($copy) {
                                $name === $old_name ? $name .= '_copy' : '';
                                $title === $old_title ? $title .= ' (copy)' : '';

                                $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                     ->setBase($old_name)
                                     ->create();

                            } else {
                                $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                     ->setBase($old_name)
                                     ->update();
                            }
                        } else {
                            $title === '' ? $title = ucfirst($name) : '';
                            $author === '' ? $author = substr(cs('txp_login_public'), 10) : '';
                            $version === '' ? $version = '0.0.1' : '';

                            $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                 ->create();
                        }
                        break;
                    case 'multi_edit':
                        extract(psa(array(
                            'edit_method',
                            'selected',
                            'clean',
                        )));

                        if (!$selected || !is_array($selected)) {
                            return;
                        }

                        $this->setNames(ps('selected'));

                        switch ($edit_method) {
                            case 'export':
                                $this->export($clean, true);
                                break;
                            case 'duplicate':
                                $this->duplicate();
                                break;
                            case 'import':
                                $this->import($clean, true);
                                break;
                            case 'delete':
                                $this->delete($clean);
                                break;
                        }
                        break;
                    case 'edit':
                        break;
                    case 'import':
                        $this->setNames(array(ps('skins')))->import();
                        break;
                    case $event.'_change_pageby':
                        $this->getPaginator()->change();
                        break;
                }

                return $this->render($step);
            }
        }

        /**
         * Render (echo) the $step related admin tab.
         *
         * @param string $step
         */

        public function render($step)
        {
            $message = $this->getMessage();

            if ($step === 'edit') {
                echo $this->getEditForm($message);
            } else {
                echo $this->getList($message);
            }
        }

        /**
         * {@inheritdoc}
         */

        public function getList($message = '')
        {
            $event = self::getEvent();

            pagetop(gTxt('tab_'.$event), $message);

            extract(gpsa(array(
                'page',
                'sort',
                'dir',
                'crit',
                'search_method',
            )));

            if ($sort === '') {
                $sort = get_pref($event.'_sort_column', 'name');
            } else {
                $sortOpts = array(
                    'title',
                    'version',
                    'author',
                    'section_count',
                    'page_count',
                    'form_count',
                    'css_count',
                    'name',
                );

                in_array($sort, $sortOpts) or $sort = 'name';

                set_pref($event.'_sort_column', $sort, $event, 2, '', 0, PREF_PRIVATE);
            }

            if ($dir === '') {
                $dir = get_pref($event.'_sort_dir', 'desc');
            } else {
                $dir = ($dir == 'asc') ? 'asc' : 'desc';

                set_pref($event.'_sort_dir', $dir, $event, 2, '', 0, PREF_PRIVATE);
            }

            $search = $this->getSearchFilter(array(
                    'name' => array(
                        'column' => 'txp_skin.name',
                        'label'  => gTxt('name'),
                    ),
                    'title' => array(
                        'column' => 'txp_skin.title',
                        'label'  => gTxt('title'),
                    ),
                    'description' => array(
                        'column' => 'txp_skin.description',
                        'label'  => gTxt('description'),
                    ),
                    'author' => array(
                        'column' => 'txp_skin.author',
                        'label'  => gTxt('author'),
                    ),
                )
            );

            list($criteria, $crit, $search_method) = $search->getFilter();

            $total = $this->countRows($criteria);
            $limit = $this->getPaginator()->getLimit();

            list($page, $offset, $numPages) = pager($total, $limit, $page);

            $table = \Txp::get('Textpattern\Admin\Table');

            return $table->render(
                compact('total', 'criteria'),
                $this->getSearchBlock($search),
                $this->getCreateBlock(),
                $this->getContentBlock(compact('offset', 'limit', 'total', 'criteria', 'crit', 'search_method', 'page', 'sort', 'dir')),
                $this->getFootBlock(compact('limit', 'numPages', 'total', 'crit', 'search_method', 'page', 'sort', 'dir'))
            );
        }

        /**
         * Get the admin related search form wrapped in its div.
         *
         * @param  object $search Textpattern\Search\Filter class object.
         * @return HTML
         */

        public function getSearchBlock($search)
        {
            return n.tag(
                $search->renderForm(self::getEvent(), array('placeholder' => 'search_skins')),
                'div',
                array(
                    'class' => 'txp-layout-4col-3span',
                    'id'    => self::getEvent().'_control',
                )
            );
        }

        /**
         * Render the .txp-control-panel div.
         *
         * @return HTML div containing the 'Create' button and the import form.
         * @see        getImportForm(), getCreateButton().
         */

        protected function getCreateBlock()
        {
            if (has_privs(self::getEvent().'.edit')) {
                return tag(
                    self::getCreateButton().$this->getImportForm(),
                    'div',
                    array('class' => 'txp-control-panel')
                );
            }
        }

        /**
         * Render the skin import form.
         *
         * @return HTML The form or a message if no new skin directory is found.
         */

        protected function getImportForm()
        {
            $dirPath = $this->getDirPath();

            if (is_dir($dirPath) && is_writable($dirPath)) {
                $new = array_diff_key($this->getUploaded(), $this->getInstalled());

                if ($new) {
                    return n
                        .tag_start('form', array(
                            'id'     => $event.'_import_form',
                            'name'   => $event.'_import_form',
                            'method' => 'post',
                            'action' => 'index.php',
                        ))
                        .tag(gTxt('import_skin'), 'label', array('for' => $event.'_import'))
                        .popHelp($event.'_import')
                        .selectInput('skins', $new, '', true, false, 'skins')
                        .eInput(self::getEvent())
                        .sInput('import')
                        .fInput('submit', '', gTxt('upload'))
                        .n
                        .tag_end('form');
                }
            } else {
                return n
                    .graf(
                        span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                        gTxt('path_not_writable', array('list' => $this->getDirPath())),
                        array('class' => 'alert-block warning')
                    );
            }
        }

        protected function getPaginator() {
            return \Txp::get('\Textpattern\Admin\Paginator', self::getEvent(), '');
        }

        protected function getSearchFilter($methods) {
            return \Txp::get('Textpattern\Search\Filter', self::getEvent(), $methods);
        }

        /**
         * Render the button to create a new skin.
         *
         * @return HTML Link.
         */

        protected static function getCreateButton()
        {
            return sLink(self::getEvent(), 'edit', gTxt('create_skin'), 'txp-button');
        }

        public function getContentBlock($data)
        {
            extract($data);

            $event = self::getEvent();
            $sortSQL = $sort.' '.$dir;
            $switchDir = ($dir == 'desc') ? 'asc' : 'desc';

            if ($total < 1) {
                if ($criteria != 1) {
                    $out = graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_results_found'),
                        array('class' => 'alert-block information')
                    );
                } else {
                    $out = graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_'.$event.'_recorded'),
                        array('class' => 'alert-block error')
                    );
                }

                return $out
                       .n.tag_end('div') // End of .txp-layout-1col.
                       .n.'</div>';      // End of .txp-layout.
            }

            $rs = $this->getTableData($criteria, $sortSQL, $offset, $limit);

            if ($rs) {
                $out = n.tag_start('form', array(
                            'class'  => 'multi_edit_form',
                            'id'     => $event.'_form',
                            'name'   => 'longform',
                            'method' => 'post',
                            'action' => 'index.php',
                        ))
                        .n.tag_start('div', array('class' => 'txp-listtables'))
                        .n.tag_start('table', array('class' => 'txp-list'))
                        .n.tag_start('thead');

                $ths = hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                    '',
                    ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                );

                $thIds = array(
                    'name'          => 'name',
                    'title'         => 'title',
                    'version'       => 'version',
                    'author'        => 'author',
                    'section_count' => 'tab_sections',
                    'page_count'    => 'tab_pages',
                    'form_count'    => 'tab_forms',
                    'css_count'     => 'tab_style',
                );

                foreach ($thIds as $thId => $thVal) {
                    $thClass = 'txp-list-col-'.$thId
                              .($thId == $sort ? ' '.$dir : '')
                              .($thVal !== $thId ? ' '.$event.'_detail' : '');

                    $ths .= column_head($thVal, $thId, $event, true, $switchDir, $crit, $search_method, $thClass);
                }

                $out .= tr($ths)
                    .n.tag_end('thead')
                    .n.tag_start('tbody');

                while ($a = nextRow($rs)) {
                    extract($a, EXTR_PREFIX_ALL, $event);

                    $editUrl = array(
                        'event'         => $event,
                        'step'          => 'edit',
                        'name'          => $skin_name,
                        'sort'          => $sort,
                        'dir'           => $dir,
                        'page'          => $page,
                        'search_method' => $search_method,
                        'crit'          => $crit,
                    );

                    $tdAuthor = txpspecialchars($skin_author);

                    empty($skin_author_uri) or $tdAuthor = href($tdAuthor, $skin_author_uri);

                    $tds = td(fInput('checkbox', 'selected[]', $skin_name), '', 'txp-list-col-multi-edit')
                        .hCell(
                            href(txpspecialchars($skin_name), $editUrl, array('title' => gTxt('edit'))),
                            '',
                            array(
                                'scope' => 'row',
                                'class' => 'txp-list-col-name',
                            )
                        )
                        .td(txpspecialchars($skin_title), '', 'txp-list-col-title')
                        .td(txpspecialchars($skin_version), '', 'txp-list-col-version')
                        .td($tdAuthor, '', 'txp-list-col-author');

                    $countNames = array('section', 'page', 'form', 'css');

                    foreach ($countNames as $name) {
                        if (${$event.'_'.$name.'_count'} > 0) {
                            if ($name === 'section') {
                                $linkParams = array(
                                    'event'         => 'section',
                                    'search_method' => $event,
                                    'crit'          => '"'.$skin_name.'"',
                                );
                            } else {
                                $linkParams = array(
                                    'event' => $name,
                                    $event  => $skin_name,
                                );
                            }

                            $tdVal = href(
                                ${$event.'_'.$name.'_count'},
                                $linkParams,
                                array(
                                    'title' => gTxt(
                                        $event.'_count_'.$name,
                                        array('{num}' => ${$event.'_'.$name.'_count'})
                                    )
                                )
                            );
                        } else {
                            $tdVal = 0;
                        }

                        $tds .= td($tdVal, '', 'txp-list-col-'.$name.'_count');
                    }

                    $out .= tr($tds, array('id' => 'txp_skin_'.$skin_name));
                }

                return $out
                        .n.tag_end('tbody')
                       .n.tag_end('table')
                       .n.tag_end('div');
            }
        }

        public function getFootBlock($data)
        {
            extract($data);

            return self::getMultiEditForm($page, $sort, $dir, $crit, $search_method)
                   .$this->getPaginator()->render()
                   .nav_form(self::getEvent(), $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);
        }

        /**
         * Render a multi-edit form widget.
         *
         * @param  int    $page          The current page number
         * @param  string $sort          The current sorting value
         * @param  string $dir           The current sorting direction
         * @param  string $crit          The current search criteria
         * @param  string $search_method The current search method
         * @return HTML
         */

        public function getMultiEditForm($page, $sort, $dir, $crit, $search_method)
        {
            $removeExtra = checkbox2('clean', get_pref('remove_extra_templates', true), 0, 'clean')
                           .n.tag(gtxt('remove_extra_templates'), 'label', array('for' => 'clean'))
                           .popHelp('remove_extra_templates');

            $removeAll = checkbox2('clean', get_pref('remove_extra_templates', true), 0, 'clean')
                         .n.tag(gtxt('remove_'.$event.'_files'), 'label', array('for' => 'clean'))
                         .popHelp('remove_'.$event.'_files');

            $methods = array(
                'import'    => array('label' => gTxt('import'), 'html' => $removeExtra),
                'duplicate' => gTxt('duplicate'),
                'export'    => array('label' => gTxt('export'), 'html' => $removeExtra),
                'delete'    => array('label' => gTxt('delete'), 'html' => $removeAll),
            );

            return multi_edit($methods, self::getEvent(), 'multi_edit', $page, $sort, $dir, $crit, $search_method);
        }

        /**
         * Render the edit form.
         *
         * @param  mixed $message
         * @return HTML
         */

        public function getEditForm($message = '')
        {
            global $step;

            $event = self::getEvent();

            require_privs($event.'.edit');

            !$message ?: pagetop(gTxt('tab_skins'), $message);

            extract(gpsa(array(
                'page',
                'sort',
                'dir',
                'crit',
                'search_method',
                'name',
            )));

            $fields = array('name', 'title', 'version', 'description', 'author', 'author_uri');

            if ($name) {
                $rs = $this->setName($name)->getRow();

                if (!$rs) {
                    return $this->main();
                }

                $caption = gTxt('edit_skin');
                $extraAction = href(
                    '<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'),
                    '#',
                    array(
                        'class'     => 'txp-clone',
                        'data-form' => $event.'_form',
                    )
                );
            } else {
                $rs = array_fill_keys($fields, '');
                $caption = gTxt('create_skin');
                $extraAction = '';
            }

            extract($rs, EXTR_PREFIX_ALL, $event);
            pagetop(gTxt('tab_skins'));

            $content = hed($caption, 2);

            foreach ($fields as $field) {
                $current = ${$event.'_'.$field};

                if ($field === 'description') {
                    $input = text_area($field, 0, 0, $current, $event.'_'.$field);
                } elseif ($field === 'name') {
                    $input = '<input type="text" value="'.$current.'" id="'.$event.'_'.$field.'" name="'.$field.'" size="'.INPUT_REGULAR.'" maxlength="63" required />';
                } else {
                    $type = ($field === 'author_uri') ? 'url' : 'text';
                    $input = fInput($type, $field, $current, '', '', '', INPUT_REGULAR, '', $event.'_'.$field);
                }

                $content .= inputLabel($event.'_'.$field, $input, $event.'_'.$field);
            }

            $content .= pluggable_ui($event.'_ui', 'extend_detail_form', '', $rs)
                .graf(
                    $extraAction.
                    sLink($event, '', gTxt('cancel'), 'txp-button')
                    .fInput('submit', '', gTxt('save'), 'publish'),
                    array('class' => 'txp-edit-actions')
                )
                .eInput($event)
                .sInput('save')
                .hInput('old_name', $skin_name)
                .hInput('old_title', $skin_title)
                .hInput('search_method', $search_method)
                .hInput('crit', $crit)
                .hInput('page', $page)
                .hInput('sort', $sort)
                .hInput('dir', $dir);

            return form($content, '', '', 'post', 'txp-edit', '', $event.'_form');
        }
    }
}

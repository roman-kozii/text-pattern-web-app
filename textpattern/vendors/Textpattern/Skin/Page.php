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

    class Page extends AssetBase
    {
        protected static $string = 'page';
        protected static $table = 'txp_page';
        protected static $dir = 'pages';
        protected static $fileContentsField = 'user_html';
        protected static $essential = array(
            array(
                'name'      => 'default',
                'user_html' => '<!-- Here goes the default page contents. -->',
            ),
            array(
                'name'      => 'error_default',
                'user_html' => '<!-- Here goes the default error page contents. -->',
            ),
        );

        /**
         * $infos+$name properties setter.
         *
         * @param  string $name      Page name;
         * @param  string $user_html Page contents;
         * @return object $this The current class object (chainable).
         */

        public function setInfos(
            $name,
            $user_html = null
        ) {
            $name = sanitizeForTheme($name);

            $this->infos = compact('name', 'user_html');
            $this->setName($name);

            return $this;
        }
    }
}

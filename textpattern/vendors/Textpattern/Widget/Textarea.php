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
 * A &lt;textarea /&gt; tag.
 *
 * @since   4.7.0
 * @package Widget
 */

namespace Textpattern\Widget;

class Textarea extends Tag implements \Textpattern\Widget\WidgetInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var null
     */

    protected $key = null;

    /**
     * Construct a single textarea widget.
     *
     * @param string $name    The textarea key (HTML name attribute)
     * @param string $content The default content to assign
     */

    public function __construct($name, $content = '')
    {
        $this->key = $name;

        parent::__construct('textarea');
        $this->setAtts(array(
                'name' => $this->key,
            ));

        $this->setContent(txpspecialchars($content));
    }

    /**
     * Fetch the key (id) in use by this text input.
     *
     * @return string
     */

    public function getKey()
    {
        return $this->key;
    }
}

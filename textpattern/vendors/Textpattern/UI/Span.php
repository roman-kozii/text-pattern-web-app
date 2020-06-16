<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2020 The Textpattern Development Team
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
 * Create a &lt;span&gt; tag.
 *
 * Replaces span().
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Span extends Tag implements UIInterface
{
    /**
     * Construct content for the span.
     *
     * @param string  $content The span content
     */

    public function __construct($content)
    {
        parent::__construct('span');

        $this->setContent($content);
    }
}

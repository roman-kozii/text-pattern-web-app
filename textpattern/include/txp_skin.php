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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Themes (skins) panel.
 *
 * @package Admin\Skin
 */

use Textpattern\Search\Filter;
use Textpattern\Skin\Main as Skins;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'skin') {
    require_privs('skin');

    $available_steps = array(
        'change_pageby' => true,
        'list'          => false,
        'edit'          => false,
        'save'          => true,
        'import'        => false,
        'multi_edit'    => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        call_user_func('skin_'.$step);
    } else {
        skin_list();
    }
}

/**
 * The main panel listing all skins.
 *
 * @param string|array $message The activity message
 */

function skin_list($message = '')
{
    global $event;

    pagetop(gTxt('tab_skins'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('skin_sort_column', 'name');
    } else {
        $sort_options = array(
            'title',
            'version',
            'author',
            'section_count',
            'page_count',
            'form_count',
            'css_count',
            'name',
        );

        in_array($sort, $sort_options) ? $sort = 'name' : '';

        set_pref('skin_sort_column', $sort, 'skin', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('skin_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('skin_sort_dir', $dir, 'skin', 2, '', 0, PREF_PRIVATE);
    }

    $sort_sql = $sort.' '.$dir;
    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter(
        $event,
        array(
            'name' => array(
                'column' => 'txp_skin.name',
                'label'  => gTxt('name'),
            ),
            'title' => array(
                'column' => 'txp_skin.title',
                'label'  => gTxt('title'),
            ),
            'author' => array(
                'column' => 'txp_skin.author',
                'label'  => gTxt('author'),
            ),
        )
    );

    list($criteria, $crit, $search_method) = $search->getFilter();

    $search_render_options = array('placeholder' => 'search_skins');
    $total = safe_count('txp_skin', $criteria);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_skins'), 1, array('class' => 'txp-heading')),
            'div',
            array('class' => 'txp-layout-4col-alt')
        );

    $searchBlock =
        n.tag(
            $search->renderForm('skin', $search_render_options),
            'div',
            array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock = array();

    if (has_privs('skin.edit')) {
        $createBlock[] =
            n.tag(
                sLink('skin', 'edit', gTxt('create_skin'), 'txp-button').
                Skins::renderImportForm(),
                'div',
                array('class' => 'txp-control-panel')
            );
    }

    $contentBlockStart = n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        ));

    $createBlock = implode(n, $createBlock);

    if ($total < 1) {
        if ($criteria != 1) {
            echo $searchBlock.
                $contentBlockStart.
                $createBlock.
                graf(
                    span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                    gTxt('no_results_found'),
                    array('class' => 'alert-block information')
                ).
                n.tag_end('div'). // End of .txp-layout-1col.
                n.'</div>'; // End of .txp-layout.
        }

        return;
    }

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo $searchBlock.$contentBlockStart.$createBlock;

    $rs = safe_rows_start(
        '*,
            (SELECT COUNT(*) FROM '.safe_pfx_j('txp_section').' WHERE txp_section.skin = txp_skin.name) section_count,
            (SELECT COUNT(*) FROM '.safe_pfx_j('txp_page').' WHERE txp_page.skin = txp_skin.name) page_count,
            (SELECT COUNT(*) FROM '.safe_pfx_j('txp_form').' WHERE txp_form.skin = txp_skin.name) form_count,
            (SELECT COUNT(*) FROM '.safe_pfx_j('txp_css').' WHERE txp_css.skin = txp_skin.name) css_count',
        'txp_skin',
        "{$criteria} order by {$sort_sql} limit {$offset}, {$limit}"
    );

    if ($rs) {
        echo n.tag_start('form', array(
                'class'  => 'multi_edit_form',
                'id'     => 'skin_form',
                'name'   => 'longform',
                'method' => 'post',
                'action' => 'index.php',
            )).
            n.tag_start('div', array('class' => 'txp-listtables')).
            n.tag_start('table', array('class' => 'txp-list')).
            n.tag_start('thead');

        $col_heads = array(
            'name'          => 'name',
            'title'         => 'title',
            'version'       => 'version',
            'author'        => 'author',
            'section_count' => 'tab_sections',
            'page_count'    => 'tab_pages',
            'form_count'    => 'tab_forms',
            'css_count'     => 'tab_style',
        );

        $column_heads = hCell(
            fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
            '',
            ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
        );

        foreach ($col_heads as $head_id => $head_value) {
            $column_heads .= column_head($head_value, $head_id, 'skin', true, $switch_dir, $crit, $search_method, (($head_id == $sort) ? "$dir " : '').'txp-list-col-'.$head_id.($head_value !== $head_id ? ' skin_detail' : ''));
        }

        echo tr($column_heads).
            n.tag_end('thead').
            n.tag_start('tbody');

        while ($a = nextRow($rs)) {
            extract($a, EXTR_PREFIX_ALL, 'skin');

            $edit_url = array(
                'event'         => 'skin',
                'step'          => 'edit',
                'name'          => $skin_name,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            $author = ($skin_author_uri) ? href(txpspecialchars($skin_author), $skin_author_uri) : txpspecialchars($skin_author);

            if ($skin_section_count > 0) {
                $sectionLink = href(
                    $skin_section_count,
                    array(
                        'event'         => 'section',
                        'search_method' => 'skin',
                        'crit'          => '"'.$skin_name.'"',
                    ),
                    array('title' => gTxt('section_count', array('{num}' => $skin_section_count)))
                );
            } else {
                $sectionLink = 0;
            }

            if ($skin_page_count > 0) {
                $pageLink = href(
                    $skin_page_count,
                    array(
                        'event' => 'page',
                        'skin'  => $skin_name,
                    ),
                    array('title' => gTxt('skin_count_page', array('{num}' => $skin_page_count)))
                );
            } else {
                $pageLink = 0;
            }

            if ($skin_css_count > 0) {
                $cssLink = href(
                    $skin_css_count,
                    array(
                        'event' => 'css',
                        'skin'  => $skin_name,
                    ),
                    array('title' => gTxt('skin_count_css', array('{num}' => $skin_css_count)))
                );
            } else {
                $cssLink = 0;
            }

            if ($skin_form_count > 0) {
                $formLink = href(
                    $skin_form_count,
                    array(
                        'event' => 'form',
                        'skin'  => $skin_name,
                    ),
                    array('title' => gTxt('skin_count_form', array('{num}' => $skin_form_count)))
                );
            } else {
                $formLink = 0;
            }

            $tds = td(fInput('checkbox', 'selected[]', $skin_name), '', 'txp-list-col-multi-edit').
                hCell(
                    href(txpspecialchars($skin_name), $edit_url, array('title' => gTxt('edit'))),
                    '',
                    array(
                        'scope' => 'row',
                        'class' => 'txp-list-col-name',
                    )
                ).
                td(txpspecialchars($skin_title), '', 'txp-list-col-title').
                td(txpspecialchars($skin_version), '', 'txp-list-col-version').
                td($author, '', 'txp-list-col-author').
                td($sectionLink, '', 'txp-list-col-section_count').
                td($pageLink, '', 'txp-list-col-page_count').
                td($formLink, '', 'txp-list-col-form_count').
                td($cssLink, '', 'txp-list-col-css_count');

            echo tr($tds, array('id' => 'txp_skin_'.$skin_name));
        }

        echo n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div'). // End of .txp-listtables.
            skin_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            n.tag_start(
                'div',
                array(
                    'class' => 'txp-navigation',
                    'id'    => $event.'_navigation',
                )
            ).
            $paginator->render().
            nav_form('skin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div');
    }

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.
}

/**
 * The editor for skins.
 */

function skin_edit($message = null)
{
    global $step;

    require_privs('skin.edit');

    $message ? pagetop(gTxt('tab_skins'), $message) : '';

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
        'name',
    )));

    $fields = array('name', 'title', 'version', 'description', 'author', 'author_uri');

    if ($name && $step == 'edit') {
        try {
            $rs = Txp::get('\Textpattern\Skin\Skin', $name)->getRow();
        } catch (\Exception $e) {
            return skin_list($e->getMessage());
        }

        $caption = gTxt('edit_skin');
        $extra_action = href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#', array(
            'class'     => 'txp-clone',
            'data-form' => 'skin_form',
        ));
    } else {
        $rs = array_fill_keys($fields, '');
        $caption = gTxt('create_skin');
        $extra_action = '';
    }

    extract($rs, EXTR_PREFIX_ALL, 'skin');
    pagetop(gTxt('tab_skins'));

    $out = array();
    $out[] = hed($caption, 2);

    foreach ($fields as $field) {
        $current = ${"skin_".$field};

        if ($field === 'description') {
            $input = text_area($field, 0, 0, $current, "skin_$field");
        } elseif ($field === 'name') {
            $input = '<input type="text" value="'.$current.'" id="skin_'.$field.'" name="'.$field.'" size="'.INPUT_REGULAR.'" maxlength="63" required />';
        } else {
            $type = ($field === 'author_uri') ? 'url' : 'text';
            $input = fInput($type, $field, $current, '', '', '', INPUT_REGULAR, '', "skin_$field");
        }

        $out[] = inputLabel("skin_$field", $input, "skin_$field");
    }

    $out[] = pluggable_ui('skin_ui', 'extend_detail_form', '', $rs).
        graf(
            $extra_action.
            sLink('skin', '', gTxt('cancel'), 'txp-button').
            fInput('submit', '', gTxt('save'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('skin').
        sInput('save').
        hInput('old_name', $skin_name).
        hInput('old_title', $skin_title).
        hInput('search_method', $search_method).
        hInput('crit', $crit).
        hInput('page', $page).
        hInput('sort', $sort).
        hInput('dir', $dir);

    echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'skin_form');
}

/**
 * Saves a skin.
 */

function skin_save()
{
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
        $instance = Txp::get('\Textpattern\Skin\Main', $old_name);
        $name = strtolower(sanitizeForUrl($name));

        if ($copy) {
            $name === $old_name ? $name .= '_copy' : '';
            $title === $old_title ? $title .= ' (copy)' : '';

            $result = $instance->duplicateAs($name, $title, $version, $description, $author, $author_uri);
        } else {
            $result = $instance->edit($name, $title, $version, $description, $author, $author_uri);
        }
    } else {
        $title ?: $title = $name;
        $author ?: $author = substr(cs('txp_login_public'), 10);
        $version ?: $version = '0.0.1';

        $result = Txp::get('\Textpattern\Skin\Main')->create(
            strtolower(sanitizeForUrl($name)),
            $title,
            $version,
            $description,
            $author,
            $author_uri
        );
    }

    skin_list($result);
}

/**
 * Changes and saves the 'pageby' value.
 */

function skin_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    skin_list();
}

/**
 * Renders a multi-edit form widget.
 *
 * @param  int    $page          The page number
 * @param  string $sort          The current sorting value
 * @param  string $dir           The current sorting direction
 * @param  string $crit          The current search criteria
 * @param  string $search_method The current search method
 * @return string HTML
 */

function skin_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    $clean = tag(gtxt('remove_extra_templates'), 'label', array('for' => 'clean')).
             popHelp('remove_extra_templates').
             checkbox('clean', 1, true);

    $methods = array(
        'update'      => array('label' => gTxt('update'), 'html' => $clean),
        'duplicate'   => gTxt('duplicate'),
        'export'      => array('label' => gTxt('export'), 'html' => $clean),
        'delete'      => gTxt('delete'),
    );

    return multi_edit($methods, 'skin', 'multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function skin_multi_edit()
{
    extract(psa(array(
        'edit_method',
        'selected',
        'clean',
    )));

    if (!$selected || !is_array($selected)) {
        return skin_list();
    }

    $instance = Txp::get('\Textpattern\Skin\Main', ps('selected'));

    switch ($edit_method) {
        case 'export':
            $edit = $instance->export($clean);
            break;
        case 'duplicate':
            $edit = $instance->duplicate();
            break;
        case 'update':
            $edit = $instance->update($clean);
            break;
        default: // delete.
            $edit = $instance->$edit_method();
            break;
    }

    skin_list($edit);
}

/**
 * Imports an uploaded skin into the database.
 */

function skin_import()
{
    skin_list(Txp::get('\Textpattern\Skin\Main', ps('skins'))->import());
}

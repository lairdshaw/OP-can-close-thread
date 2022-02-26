<?php

/*
 * OP Can Close Thread, a plugin for MyBB 1.8.x.
 * Copyright (C) 2021-2022 Laird Shaw.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// Disallow direct access to this file for security reasons.
if (!defined('IN_MYBB')) {
	die('Direct access to this file is not allowed.');
}

if (!defined('IN_ADMINCP')) {
	$plugins->add_hook('showthread_end'                    , 'opcanclosethread_hookin__showthread_end'                            );
	$plugins->add_hook('newthread_end'                     , 'opcanclosethread_hookin__newthread_or_newreply_end'                 );
	$plugins->add_hook('newreply_end'                      , 'opcanclosethread_hookin__newthread_or_newreply_end'                 );
	$plugins->add_hook('datahandler_post_insert_thread_end', 'opcanclosethread_hookin__datahandler_post_insert_thread_end'        );
	$plugins->add_hook('datahandler_post_insert_post_end'  , 'opcanclosethread_hookin__datahandler_post_insert_or_update_post_end');
	$plugins->add_hook('datahandler_post_update_end'       , 'opcanclosethread_hookin__datahandler_post_insert_or_update_post_end');
	$plugins->add_hook('moderation_start'                  , 'opcanclosethread_hookin__moderation_start'                          );
	$plugins->add_hook('class_moderation_open_threads'     , 'opcanclosethread_hookin__class_moderation_open_threads'             );
	$plugins->add_hook('editpost_end'                      , 'opcanclosethread_hookin__editpost_end'                              );
}

const c_opcct_patches = array(
	array(
		'file' => 'xmlhttp.php',
		'from' => "		if(\$thread['closed'] == 1)",
		'to'   => "		if(\$thread['closed'] == 1/*Begin OPCanClThr patch*/ && !(function_exists('opcct_can_edit_thread') && opcct_can_edit_thread(\$thread, \$mybb->user['uid']))/*End OPCanClThr patch*/)"
	),
	array(
		'file' => 'editpost.php',
		'from' => "			error(\$lang->redirect_threadclosed);",
		'to'   => "/*Begin OPCanClThr patch*/
			if (!(function_exists('opcct_can_edit_thread') && opcct_can_edit_thread(\$thread, \$mybb->user['uid']))) {
/*End OPCanClThr patch (other than additional tab on next line) */
				error(\$lang->redirect_threadclosed);
/*Begin OPCanClThr patch*/
			}
/*End OPCanClThr patch*/",
	),
	array(
		'file' => 'inc/functions_post.php',
		'from' => "\$thread['closed'] != 1 && ",
		'to'   => "/*Begin OPCanClThr patch*/(/*End OPCanClThr patch*/\$thread['closed'] != 1/*Begin OPCanClThr patch*/ || function_exists('opcct_can_edit_thread') && opcct_can_edit_thread(\$thread, \$mybb->user['uid']))/*End OPCanClThr patch*/ && ",
	),
	array(
		'file' => 'newreply.php',
		'from' => "		error(\$lang->redirect_threadclosed);",
		'to'   => "/*Begin OPCanClThr patch*/
		if (function_exists('opcct_can_edit_thread')) {
			if (!opcct_can_edit_thread(\$thread, \$mybb->user['uid'])) {
				\$lang->load('opcanclosethread');
				error(\$lang->opcct_err_no_thread_closed_by_op);
			}
		} else {
/*End OPCanClThr patch (other than additional tab on next line) */
			error(\$lang->redirect_threadclosed);
/*Begin OPCanClThr patch*/
		}
/*End OPCanClThr patch*/"
	),
	array(
		'file' => 'showthread.php',
		'from' => "	\$quickreply = '';
	if(\$forumpermissions['canpostreplys'] != 0 && \$mybb->user['suspendposting'] != 1 && (\$thread['closed'] != 1",
		'to'   => "	\$quickreply = '';
	if(\$forumpermissions['canpostreplys'] != 0 && \$mybb->user['suspendposting'] != 1 && (\$thread['closed'] != 1/*Begin OPCanClThr patch*/ || function_exists('opcct_can_edit_thread') && opcct_can_edit_thread(\$thread, \$mybb->user['uid'])/*End OPCanClThr patch*/",
	),
);

function opcanclosethread_info() {
	global $lang, $plugins_cache, $cache;

	$lang->load('opcanclosethread');

	$info = array(
		'name'          => $lang->opcct_name,
		'description'   => $lang->opcct_desc,
		'author'        => 'Laird Shaw',
		'authorsite'    => 'https://creativeandcritical.net/',
		'version'       => '1.1.1-dev-post-release',
		'codename'      => 'opcanclosethread',
		'compatibility' => '18*'
	);

	if (empty($plugins_cache) || !is_array($plugins_cache)) {
		$plugins_cache = $cache->read('plugins');
	}
	$active_plugins = $plugins_cache['active'];
	$list_items = '';
	if ($active_plugins && $active_plugins['opcanclosethread']) {
		list($unwritable_files, $fpcfalse_files, $failedpatch_files) = opcct_realise_missing_patches();
		if ($unwritable_files) {
			$info['description'] .= '<ul><li style="list-style-image: url(styles/default/images/icons/warning.png)"><span style="color: red;">'.$lang->sprintf($lang->opcct_unwritable, implode($lang->comma, $unwritable_files)).'</span></li></ul>'.PHP_EOL;
		}
		if ($fpcfalse_files) {
			$info['description'] .= '<ul><li style="list-style-image: url(styles/default/images/icons/warning.png)"><span style="color: red;">'.$lang->sprintf($lang->opcct_fpcfalse, implode($lang->comma, $fpcfalse_files)).'</span></li></ul>'.PHP_EOL;
		}
		if ($failedpatch_files) {
			$info['description'] .= '<ul><li style="list-style-image: url(styles/default/images/icons/warning.png)"><span style="color: red;">'.$lang->sprintf($lang->opcct_unpatchable, implode($lang->comma, $failedpatch_files)).'</span></li></ul>'.PHP_EOL;
		}
		if (!$unwritable_files && !$fpcfalse_files && !$failedpatch_files) {
			$patched_files = array();
			foreach (c_opcct_patches as $patch) {
				if (!in_array($patch['file'], $patched_files)) {
					$patched_files[] = $patch['file'];
				}
			}
			$info['description'] .= '<ul><li style="list-style-image: url(styles/default/images/icons/success.png)"><span style="color: green;">'.$lang->sprintf($lang->opcct_all_patched, implode($lang->comma, $patched_files)).'</span></li></ul>'.PHP_EOL;
		}
	}

	return $info;
}

function opcanclosethread_install() {
	global $db, $lang;

	$lang->load('opcanclosethread');

	$res = $db->query('SELECT MAX(disporder) as max_disporder FROM '.TABLE_PREFIX.'settinggroups');
	$disporder = intval($db->fetch_field($res, 'max_disporder')) + 1;

	// Insert the plugin's settings group into the database.
	$setting_group = array(
		'name'         => 'opcanclosethread_settings',
		'title'        => $db->escape_string($lang->opcct_settings_title),
		'description'  => $db->escape_string($lang->opcct_settings_desc),
		'disporder'    => $disporder,
		'isdefault'    => 0
	);
	$db->insert_query('settinggroups', $setting_group);
	$gid = $db->insert_id();

	// Now insert each of its settings values into the database...
	$settings = array(
		'opcanclosethread_opclosable_forums' => array(
			'title'       => $lang->opcct_setting_opclosable_forums_title,
			'description' => $lang->opcct_setting_opclosable_forums_desc,
			'optionscode' => 'forumselect',
			'value'       => '',
		),
	);

	$disporder = 1;
	foreach ($settings as $name => $setting) {
		$insert_settings = array(
			'name'        => $db->escape_string($name),
			'title'       => $db->escape_string($setting['title']),
			'description' => $db->escape_string($setting['description']),
			'optionscode' => $db->escape_string($setting['optionscode']),
			'value'       => $db->escape_string($setting['value']),
			'disporder'   => $disporder,
			'gid'         => $gid,
			'isdefault'   => 0
		);
		$db->insert_query('settings', $insert_settings);
		$disporder++;
	}

	rebuild_settings();

	// This plugin was originally part of the Bump Absorber plugin, so it may be that
	// we need to convert the added column name's prefix based on the abbreviations of that
	// plugin and this one...
	if ($db->field_exists('ba_closed_by_author', 'threads') && !$db->field_exists('opcct_closed_by_author', 'threads')) {
		$db->write_query('ALTER TABLE '.TABLE_PREFIX.'threads CHANGE ba_closed_by_author opcct_closed_by_author tinyint(1) NOT NULL DEFAULT 0');
	}

	if (!$db->field_exists('opcct_closed_by_author', 'threads')) {
		$db->add_column('threads', 'opcct_closed_by_author', 'tinyint(1) NOT NULL DEFAULT 0');
	}

	// ...or just delete the original outright.
	if ($db->field_exists('ba_closed_by_author', 'threads')) {
		$db->drop_column('threads', 'ba_closed_by_author');
	}

	// Insert the plugin's templates into the database.
	$templateset = array(
		'prefix' => 'opcanclosethread',
		'title' => $lang->opcct_templateset_name,
	);
	$db->insert_query('templategroups', $templateset);

	$tpl = '<form method="post" action="moderation.php?action=opcct_toggle_own_thread_closed&amp;tid={$tid}" style="display: inline;"><input type="hidden" name="my_post_key" value="{$mybb->post_code}" /><input type="submit" name="submit" value="{$caption}" style="padding: 6px 8px 7px 8px; display: inline-block; font-size: 14px; color: #fff; border-radius: 6px; background-color: #2c2c2c; border: 1px solid #2c2c2c; font-family: Tahoma,Verdana,Arial,Sans-Serif; cursor: pointer;" /></form>';

	$db->insert_query('templates', array(
		'title'    => 'opcanclosethread_openclose_button',
		'template' => $db->escape_string($tpl),
		'sid'      => '-2',
		'version'  => '1',
		'dateline' => TIME_NOW
	));
}

function opcanclosethread_uninstall() {
	global $db, $mybb;

	if ($mybb->request_method != 'post') {
		global $page, $lang;
		$lang->load('opcanclosethread');
		$page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=opcanclosethread', $lang->opcct_confirm_uninstall, $lang->opcct_confirm_uninstall_title);
	}

	$rebuild_settings = false;
	$query = $db->simple_select('settinggroups', 'gid', "name = 'opcanclosethread_settings'");
	while (($gid = $db->fetch_field($query, 'gid'))) {
		$db->delete_query('settinggroups', "gid='{$gid}'");
		$db->delete_query('settings', "gid='{$gid}'");
		$rebuild_settings = true;
	}
	if ($rebuild_settings) rebuild_settings();

	opcct_revert_patches();

	// Only remove this DB field if the admin has selected NOT to keep data.
	if (!isset($mybb->input['no'])) {
		if ($db->field_exists('opcct_closed_by_author', 'threads')) {
			$db->drop_column('threads', 'opcct_closed_by_author');
		}
	}

	$db->delete_query('templates', "title LIKE 'opcanclosethread_%'");
	$db->delete_query('templategroups', "prefix = 'opcanclosethread'");
}

function opcanclosethread_is_installed() {
	global $db;

	$query = $db->simple_select('settinggroups', 'gid', "name = 'opcanclosethread_settings'");

	return $db->fetch_field($query, 'gid') ? true : false;
}

function opcanclosethread_activate() {
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('editpost', '(\\{\\$postoptions\\})', '{$postoptions}
{$modoptions}'
	);
	find_replace_templatesets('showthread', '(\\{\\$newreply\\})', '{$opcct_btn}{$newreply}');
}

function opcanclosethread_deactivate() {
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('editpost', '(\\r?\\n\\{\\$modoptions\\})', '', 0);
	find_replace_templatesets('showthread', '(\\{\\$opcct_btn\\})', '', 0);
}

function opcct_can_edit_thread($thread, $uid = -1) {
	global $mybb;

	if ($uid == -1) {
		$uid = $mybb->user['uid'];
	}

	return $thread['opcct_closed_by_author'] == 1 && $uid == $thread['uid'] && ($mybb->settings['opcanclosethread_opclosable_forums'] == -1 || in_array($thread['fid'], explode(',', $mybb->settings['opcanclosethread_opclosable_forums']))) && is_member($mybb->settings['opcanclosethread_auth_ugs']);
}

// Show the "Close Thread" checkbox when starting a thread in a forum stipulated
// in this plugin's settings.
function opcanclosethread_hookin__newthread_or_newreply_end() {
	global $modoptions, $bgcolor, $stickoption, $closeoption, $mybb, $templates, $lang, $fid, $thread;

	if (($mybb->settings['opcanclosethread_opclosable_forums'] == -1
	     ||
	     in_array($fid, explode(',', $mybb->settings['opcanclosethread_opclosable_forums']))
	    )
	    &&
	    is_member($mybb->settings['opcanclosethread_auth_ugs'])
	    &&
	    (!is_moderator($fid)
	     ||
	     !is_moderator($fid, 'canopenclosethreads')
	    )
	   ) {
		if (!isset($closeoption)) {
			$closeoption = '';
		}
		if (!isset($modoptions)) {
			$modoptions = '';
		}
		if (!empty($mybb->input['previewpost']) || $mybb->get_input('submit')) {
			$modopts = $mybb->get_input('modoptions', MyBB::INPUT_ARRAY);
			$closecheck = !empty($modopts['closethread']) ? 'checked="checked"' : '';
		} else if (!empty($thread) && $thread['closed']) {
			$closecheck = 'checked="checked"';
		}
		eval('$closeoption .= "'.$templates->get('newreply_modoptions_close').'";');
		eval('$modoptions = "'.$templates->get('newreply_modoptions').'";');
	}
}

// Show the "Close Thread" checkbox in the quick reply box when viewing a thread
// if the current user is the thread's author, and not a moderator, in a forum
// stipulated in this plugin's settings.
//
// Also in that same scenario show the "[Close/Open] Thread" button top and bottom
// of page beside the "New Reply" button, and restore the "New Reply" button from
// its "Thread Closed" variant as necessary.
function opcanclosethread_hookin__showthread_end() {
	global $mybb, $templates, $lang, $theme, $moderation_notice, $tid, $reply_subject, $posthash, $last_pid, $page, $collapsedthead, $collapsedimg, $expaltext, $collapsed, $trow, $option_signature, $closeoption, $captcha, $thread, $quickreply, $opcct_btn, $newreply;

	if (($mybb->settings['opcanclosethread_opclosable_forums'] == -1
	     ||
	     in_array($thread['fid'], explode(',', $mybb->settings['opcanclosethread_opclosable_forums']))
	    )
	    &&
	    $mybb->user['uid'] == $thread['uid']
	    &&
	    $thread['visible'] != -1
	    &&
	    !is_moderator($thread['fid'], 'canopenclosethreads')
	   ) {
		$lang->load('opcanclosethread');
		if (($thread['closed'] != 1 || $thread['opcct_closed_by_author'] == 1)
	            &&
	            !empty($quickreply)
		   ) {
			if (!isset($closeoption)) {
				$closeoption = '';
			}
			$closelinkch = $thread['closed'] ? ' checked="checked"' : '';

			eval('$closeoption .= "'.$templates->get('showthread_quickreply_options_close').'";');
			eval('$quickreply = "'.$templates->get('showthread_quickreply').'";');
		}

		$caption = '';
		$opcct_btn = '';
		if ($thread['closed'] == 1 && $thread['opcct_closed_by_author'] == 1) {
			$caption = $lang->opcct_open_thread;
		} else if ($thread['closed'] != 1) {
			$caption = $lang->opcct_close_thread;
		}
		if ($caption) {
			$opcct_btn = eval($templates->render('opcanclosethread_openclose_button'));
		}
		$newreply = eval($templates->render('showthread_newreply'));
	}
}

// Process the "Close Thread" checkbox on thread creation in a forum stipulated in
// this plugin's settings by closing the thread, but only if this would not have already
// occurred in the data handler, which it would have if the thread's author is
// a moderator with the right to open and close threads.
function opcanclosethread_hookin__datahandler_post_insert_thread_end($postHandler) {
	global $mybb, $db, $lang;

	$thread = $postHandler->data;

	if (!$thread['savedraft']
	    &&
	    (!is_moderator($thread['fid'], '', $thread['uid'])
	     ||
	     !is_moderator($thread['fid'], 'canopenclosethreads', $thread['uid'])
	    )
	    &&
	    !empty($thread['modoptions']['closethread'])
	    &&
	    ($mybb->settings['opcanclosethread_opclosable_forums'] == -1
	     ||
	     in_array($thread['fid'], explode(',', $mybb->settings['opcanclosethread_opclosable_forums']))
	    )
	   ) {
		$lang->load('moderation');

		$modlogdata['fid'] = $thread['fid'];
		$modlogdata['tid'] = $postHandler->tid;
		log_moderator_action($modlogdata, $lang->thread_closed);
		$db->update_query('threads', array('closed' => 1, 'opcct_closed_by_author' => 1), "tid='{$postHandler->tid}'");
	}
}

// Process the "Close Thread" checkbox, on reply to a thread by its author in a
// forum stipulated in this plugin's settings, by closing the thread, but only if this
// would not have already occurred in the data handler, which it would have if
// the thread's author is a moderator with the right to open and close threads.
function opcanclosethread_hookin__datahandler_post_insert_or_update_post_end($postHandler) {
	global $mybb, $db, $lang;

	$post = $postHandler->data;
	$thread = get_thread($post['tid']);

	if ($mybb->settings['opcanclosethread_opclosable_forums'] == -1
	    ||
	    in_array($thread['fid'], explode(',', $mybb->settings['opcanclosethread_opclosable_forums']))
	   ) {
		if ($mybb->user['uid'] == $thread['uid']
		    &&
		    !$post['savedraft']
		    &&
		    !is_moderator($post['fid'], 'canopenclosethreads', $post['uid'])
		   ) {
			$lang->load('datahandler_post');

			$modlogdata['fid'] = $thread['fid'];
			$modlogdata['tid'] = $thread['tid'];
			$modoptions = !empty($post['modoptions']) ? $post['modoptions'] : $mybb->get_input('modoptions', MyBB::INPUT_ARRAY);

			if (!empty($modoptions['closethread']) && $thread['closed'] != 1) {
				log_moderator_action($modlogdata, $lang->thread_closed);
				$db->update_query('threads', array('closed' => 1, 'opcct_closed_by_author' => 1), "tid='{$thread['tid']}'");
				$postHandler->return_values['closed'] = 1;
			} else if (empty($modoptions['closethread']) && $thread['closed'] == 1 && $thread['opcct_closed_by_author'] == 1) {
				log_moderator_action($modlogdata, $lang->thread_opened);
				$db->update_query('threads', array('closed' => 0, 'opcct_closed_by_author' => 0), "tid='{$thread['tid']}'");
				$postHandler->return_values['closed'] = 0;
			}
		}
	}

	if (!$post['savedraft'] && isset($post['modoptions']) && empty($modoptions['closethread']) && $thread['closed'] == 1 && is_moderator($post['fid'], 'canopenclosethreads', $post['uid'])) {
		$db->update_query('threads', array('opcct_closed_by_author' => 0), "tid = {$thread['tid']}");
	}
}

// Toggle the opening/closing of a thread by its author in a forum stipulated
// in this plugin's settings when submitted by the "[Open/Close] Thread" button
// (within a "post" form) at the top or bottom of a thread page.
function opcanclosethread_hookin__moderation_start() {
	global $mybb, $lang, $db;

	if ($mybb->get_input('action') == 'opcct_toggle_own_thread_closed' && $mybb->request_method == 'post') {
		$lang->load('moderation');
		$lang->load('opcanclosethread');

		verify_post_check($mybb->get_input('my_post_key'));

		$tid = $mybb->get_input('tid', MyBB::INPUT_INT);

		if (!$tid || !($thread = get_thread($tid))) {
			error($lang->error_invalidthread, $lang->error);
		}
		$fid = $thread['fid'];
		if (!($mybb->settings['opcanclosethread_opclosable_forums'] == -1
		      ||
		      in_array($thread['fid'], explode(',', $mybb->settings['opcanclosethread_opclosable_forums']))
		     )
		   ) {
			error($lang->opcct_err_no_close_right_in_forum, $lang->error);
		}
		if ($mybb->user['uid'] != $thread['uid']) {
			error($lang->opcct_err_no_close_right_not_author, $lang->error);
		}

		$modlogdata['tid'] = $tid;
		$modlogdata['fid'] = $fid;

		$moderation = new Moderation;
		if ($thread['visible'] == -1) {
			error($lang->error_thread_deleted, $lang->error);
		}

		if ($thread['closed'] == 1 && $thread['opcct_closed_by_author'] == 1) {
			$openclose = $lang->opened;
			$redirect = $lang->redirect_openthread;
			$moderation->open_threads($tid);
		} else if ($thread['closed'] == 0) {
			$openclose = $lang->closed;
			$redirect = $lang->redirect_closethread;
			$db->update_query('threads', array('closed' => 1, 'opcct_closed_by_author' => 1), "tid='{$tid}'");
		}
		$lang->mod_process = $lang->sprintf($lang->mod_process, $openclose);

		log_moderator_action($modlogdata, $lang->mod_process);

		moderation_redirect(get_thread_link($thread['tid']), $redirect);
	}
}

function opcanclosethread_hookin__class_moderation_open_threads($tids) {
	global $db;

	$tid_list = implode(',', $tids);
	$db->update_query('threads', array('opcct_closed_by_author' => 0), "tid IN ($tid_list)");
}

function opcanclosethread_hookin__editpost_end() {
	global $mybb, $lang, $templates, $thread, $modoptions, $fid, $bgcolor, $bgcolor2;

	$modoptions = '';
	if (($thread['closed'] == 0
	     ||
	     $thread['opcct_closed_by_author'] == 1
	    )
	    &&
	    $mybb->user['uid'] == $thread['uid']
	    &&
	    ($mybb->settings['opcanclosethread_opclosable_forums'] == -1
	     ||
	     in_array($fid, explode(',', $mybb->settings['opcanclosethread_opclosable_forums']))
	    )
	   ) {
		$lang->load('newthread');

		if (isset($mybb->input['previewpost'])) {
			$modoptions = $mybb->get_input('modoptions', MyBB::INPUT_ARRAY);
			if (isset($modoptions['closethread']) && $modoptions['closethread'] == 1) {
				$closecheck = ' checked="checked"';
			} else	$closecheck = '';
		} else {
			$closecheck = $thread['closed'] ? ' checked="checked"' : '';
		}

		eval('$closeoption = "'.$templates->get('newreply_modoptions_close').'";');
		$stickoption = '';
		eval('$modoptions = "'.$templates->get('newreply_modoptions').'";');
		$bgcolor = 'trow1';
		$bgcolor2 = 'trow2';
	}
}

function opcct_revert_patches() {
	$ids = array_keys(c_opcct_patches);
	return opcct_realise_or_revert_patches($ids, true);
}

function opcct_realise_missing_patches() {
	$ids = opcct_get_missing_patch_ids();
	return opcct_realise_or_revert_patches($ids, false);
}

function opcct_realise_or_revert_patches($ids, $revert = false) {
	$unwritable_files  = array();
	$fpcfalse_files    = array();
	$failedpatch_files = array();
	foreach ($ids as $id) {
		$entry = c_opcct_patches[$id];
		if (!file_exists(MYBB_ROOT.$entry['file'])) {
			continue;
		}
		if (!is_writable(MYBB_ROOT.$entry['file'])) {
			if (!in_array(MYBB_ROOT.$entry['file'], $unwritable_files)) {
				$unwritable_files[] = $entry['file'];
			}
		} else {
			$from = $entry[$revert ? 'to'   : 'from'];
			$to   = $entry[$revert ? 'from' : 'to'  ];
			$res = opcct_replace_in_file(MYBB_ROOT.$entry['file'], $from, $to);
			if ($res === false) {
				$fpcfalse_files[] = $entry['file'];
			} else if ($res === -1) {
				$failedpatch_files[] = $entry['file'];
			}
		}
	}

	return array(array_unique($unwritable_files), array_unique($fpcfalse_files), array_unique($failedpatch_files));
}

// Returns:
// true if the patch succeeded.
// false if the patch failed due to file_put_contents() returning false
// -1 if the patch seemed to succeed but was not present in the file upon checking for it
function opcct_replace_in_file($file, $from, $to) {
	$contents = file_get_contents($file);
	$contents_new = str_replace($from, $to, $contents);
	if (file_put_contents($file, $contents_new) === false) {
		return false;
	}
	$contents_after = file_get_contents($file);

	return strpos($contents_after, $to) !== false ? true : -1;
}

function opcct_get_missing_patch_ids() {
	$ret = array();
	foreach (c_opcct_patches as $idx => $entry) {
		if (!empty($entry['might_not_exist']) && !file_exists(MYBB_ROOT.$entry['file'])) {
			continue;
		}
		$contents = file_get_contents(MYBB_ROOT.$entry['file']);
		if (strpos($contents, $entry['to']) === false) {
			$ret[] = $idx;
		}
	}

	return $ret;
}

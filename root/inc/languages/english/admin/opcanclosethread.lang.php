<?php

$l['opcct_name'] = 'OP Can Close Thread';
$l['opcct_desc'] = 'Allows the authors of threads (aka original posters aka OPs) to close and reopen their own threads to new replies in forums stipulated in the plugin\'s ACP settings. Thread authors do not need to be a moderator to close their own threads in those forums.';

$l['opcct_settings_title'] = 'OP Can Close Thread';
$l['opcct_settings_desc' ] = 'Settings for the OP Can Close Thread plugin.';

$l['opcct_setting_opclosable_forums_title'] = 'Original poster-closable forums';
$l['opcct_setting_opclosable_forums_desc' ] = 'Select the forums the threads of which the original poster (OP) aka thread author is permitted to close/reopen (without needing to be a moderator), so long as they are a member of the group(s) selected below.';
$l['opcct_setting_auth_ugs_title'] = 'Authorised usergroups';
$l['opcct_setting_auth_ugs_desc' ] = 'Select the usergroups which can close threads in the forums selected above.';
$l['opcct_setting_autoprefix_title'] = 'Auto-prefix';
$l['opcct_setting_autoprefix_desc' ] = 'Select any prefix that should be auto-applied to a thread when it is closed by its author. Note: if you select more than one prefix, the one with the lowest ID which is authorised for the forum of a thread being closed by its author will be used. Usergroup permissions for prefixes are ignored.';
$l['opcct_setting_rem_prefix_on_reopen_title'] = 'Remove prefix on reopening?';
$l['opcct_setting_rem_prefix_on_reopen_desc'] = 'Select "Yes" to remove the thread\'s prefix when its author reopens it. Note that the prefix is removed unconditionally, that is, regardless of whether it was auto-added by this plugin or added manually.';
$l['opcct_setting_prevent_reopen_title'] = 'Block reopening?';
$l['opcct_setting_prevent_reopen_desc'] = 'Select "Yes" to prevent thread authors from reopening threads they\'ve closed';

$l['opcct_all_patched'] = 'All necessary patches have automatically been applied to the following file(s) (where they actually exist): {1}. To auto-revert them, uninstall this plugin.';
$l['opcct_unwritable' ] = 'The following file(s) is/are not writable by your web server, and patches could not be auto-applied to it/them: {1}. Please grant your web server write permissions on that/those file(s). ';
$l['opcct_fpcfalse'   ] = 'Whilst the following files(s) seem(s) to be writable by your web server, a return of false was obtained when trying to save it/them: {1}. Please ensure that your web server can write to that/those file(s). ';
$l['opcct_unpatchable'] = 'Whilst the following file(s) is/are writable by your web server, not all of the patch(es) auto-applied to them succeeded: {1}. Please check that all of the "from" fields of the patch(es) for that/those file(s) has a match in the file(s), and adjust as necessary. ';

$l['opcct_templateset_name'] = 'OP Can Close Thread';

$l['opcct_confirm_uninstall_title'] = 'OP Can Close Thread Uninstallation';
$l['opcct_confirm_uninstall'      ] = 'Do you wish to delete ALL of this plugin\'s data from the database? Selecting "Yes" executes a full uninstallation suitable for removing the plugin entirely (other than its files). Selecting "No" is suitable for upgrading: it will leave untouched the records of OP-closed threads, so that, should they choose to do so, thread authors can reopen the threads they\'ve closed after the plugin is reinstalled (otherwise, only moderators will be able to reopen those threads).';
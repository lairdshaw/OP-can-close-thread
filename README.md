## About OP Can Close Thread

OP Can Close Thread is a plugin for [MyBB](https://mybb.com/) 1.8. It allows the authors of threads (aka original posters aka OPs) to close and reopen their own threads to new replies in forums stipulated in the plugin's ACP settings if they are members of the usergroup(s) also stipulated in those settings. Thread authors do not need to be moderators to close/reopen their own threads in those forums, just a member of the stipulated usergroup(s), however, they may not reopen any of their own threads which *were* closed by a moderator.

A thread prefix can also be set to auto-apply to any thread closed by its author.

## Requirements

* MyBB 1.8.*.

## Installing

1. Download the zip archive.

2. Extract its files.

3. Copy (recursively) the files under "root" into your forum's root.

4. Install+activate the plugin via the ACP's _Plugins_ page.

5. Configure the plugin in the ACP under _Settings_ -> _Plugin Settings_ -> _OP Can Close Thread_

## Upgrading

Note: order is important here, and has changed back since an earlier release. Order matters because a patch has changed, and this ordering ensures that it is correctly auto-reverted on uninstallation based on the pre-change version, prior to the new patch being auto-applied on reinstallation. Note also that given the new order (steps 1 and 2 swapped), if you are upgrading from the very first version of this plugin (1.0.0), there will be no prompt on uninstallation which will allow you to maintain your data, and, on re-installation, threads closed by their authors will be considered by the plugin to have been closed by a moderator. The only way around this, to retain your data, is to swap the order of 1 and 2 below (so that the option to retain your data is shown on uninstallation) and manually revert the changed patch to the core file whose auto-reversion will then fail on uninstallation, prior to reinstalling so that the new patch version is auto-applied. You can find a list of patches near the top of the plugin file, and you will see that they include internal comments so they can easily be found in core files. The particular patch that has changed in the latest version is the one to newreply.php.

1. Uninstall the plugin via the ACP and select "No" when prompted as to whether to delete ALL plugin data.

2. Download, extract, and copy files as in steps one, two, and three for installing above.

3. Install+activate the plugin via the ACP.

4. Restore your setting(s) for the plugin.

## Licence

OP Can Close Thread is licensed under the GPL v3.

## Author

[Laird Shaw](https://creativeandcritical.net/) as part of the unofficial [MyBB Group](https://mybb.group/)

## Credits

OP Can Close Thread was originally part of Bump Absorber, a plugin written for a fee for a MyBB admin - [@andrewjs18](https://github.com/andrewjs18) - who asked that it then be open sourced.
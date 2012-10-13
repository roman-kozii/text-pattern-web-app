<?php

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	safe_alter('textpattern', "CHANGE COLUMN `textile_body` `textile_body` VARCHAR(32) NOT NULL DEFAULT '1', CHANGE COLUMN `textile_excerpt` `textile_excerpt` VARCHAR(32) NOT NULL DEFAULT '1';");
	safe_update('txp_prefs', "name = 'pane_article_textfilter_help_visible'", "name = 'pane_article_textile_help_visible'");

	// Rejig preferences panel.
	$core_ev = doQuote(join("','", array('site', 'admin', 'publish', 'feeds', 'custom', 'comments')));
	// 1) Increase event column size.
	safe_alter('txp_prefs', "CHANGE COLUMN `event` `event` VARCHAR(32) NOT NULL DEFAULT 'publish'");
	// 2) Remove basic/advanced distinction.
	safe_update('txp_prefs', "type = '".PREF_CORE."'", "type = '".PREF_PLUGIN."' AND event IN (".$core_ev.")");
	// 3) Consolidate existing prefs into better groups.
	safe_update('txp_prefs', "event = 'site'", "name in ('sitename', 'siteurl', 'site_slogan', 'production_status', 'gmtoffset', 'auto_dst', 'is_dst', 'dateformat', 'archive_dateformat', 'permlink_mode', 'doctype', 'logging', 'use_comments', 'expire_logs_after')");
	// 4) Reorder existing prefs into a more logical progression.
	safe_update('txp_prefs', "position = '230'", "name = 'expire_logs_after'");
	safe_update('txp_prefs', "position = '340'", "name = 'max_url_len'");
	safe_update('txp_prefs', "position = '160'", "name = 'comments_sendmail'");
	safe_update('txp_prefs', "position = '180'", "name = 'comments_are_ol'");
	safe_update('txp_prefs', "position = '200'", "name = 'comment_means_site_updated'");
	safe_update('txp_prefs', "position = '220'", "name = 'comments_require_name'");
	safe_update('txp_prefs', "position = '240'", "name = 'comments_require_email'");
	safe_update('txp_prefs', "position = '260'", "name = 'never_display_email'");
	safe_update('txp_prefs', "position = '280'", "name = 'comment_nofollow'");
	safe_update('txp_prefs', "position = '300'", "name = 'comments_disallow_images'");
	safe_update('txp_prefs', "position = '320'", "name = 'comments_use_fat_textile'");
	safe_update('txp_prefs', "position = '340'", "name = 'spam_blacklists'");

?>

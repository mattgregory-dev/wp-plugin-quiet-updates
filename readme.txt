=== Quiet Update Emails ===
Contributors: matthewgregory
Tags: updates, email, notifications, maintenance
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Silences the routine WordPress update mail you choose to silence, and keeps the rest.

== Description ==

WordPress emails after every automatic update, whether or not anything went
wrong. Across a handful of sites that is enough mail to stop being read, which
is what makes the one that matters easy to miss.

This plugin silences the categories you choose and leaves the others alone.

**Nothing is silenced until you choose it.** A fresh activation changes no mail
at all; everything is configured under Settings › Quiet Updates.

= Update result emails =

Core, plugin and theme update mail each get the same three-way choice:

* **Send every email** — WordPress behaves normally.
* **Silence successes, still send failures** — the recommended setting. The
  routine "everything updated fine" mail stops; anything reporting a failure
  still arrives, including a critical core failure where the site may be down.
* **Silence every email** — failures included. Offered because it is your site,
  not because it is a good idea.

Plugin and theme mail arrives as one digest covering a whole run, so "silence
successes" sends the digest whenever any item in the run failed.

= Notices and nags =

Three separate toggles, each off by default:

* The email announcing that a new version of WordPress is available.
* The admin-email verification screen that interrupts a login every six months.
* The automatic-update debug email, which only goes out on beta and test builds.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip
   through Plugins › Add New › Upload Plugin.
2. Activate it through the Plugins screen.
3. Visit Settings › Quiet Updates and choose what to silence. Until you
   do, the plugin changes no mail at all.

== Frequently Asked Questions ==

= Will I still know an update is available? =

Yes. The Dashboard and Updates screen are untouched; only the email is
suppressed.

= Does this stop automatic updates? =

No. It changes which emails you receive, nothing else. Which updates install,
and whether they install automatically, is controlled by the
WP_AUTO_UPDATE_CORE constant and the per-plugin auto-update settings. This
plugin neither reads nor writes them.

= Why keep the failure emails? =

A critical core update failure means the site may be down. That is the one
message worth an interruption, and a blanket suppression discards it along with
the routine mail.

= Does it work on multisite? =

It is built and tested for single sites. There is no network-admin settings
screen.

== Changelog ==

= 3.1.1 =
* Count only the plugins and themes WordPress has an update source for. A
  manually installed one has no auto-update control at all, and counting it
  made the panel disagree with the Plugins screen.

= 3.1.0 =
* Add a read-only panel showing what the site currently does with automatic
  updates, since that decides whether silencing successes makes sense.
* Say so plainly when a host or another plugin controls core updates, and drop
  the link in that case rather than sending you to a screen you cannot change.

= 3.0.0 =
* Add a settings screen. Every category is now chosen rather than assumed.
* Nothing is silenced until configured; a fresh activation changes no mail.
* Add a third choice, silencing failures too, for anyone who wants it.
* Filters are registered only when a setting calls for one.

= 2.0.0 =
* Keep core update failures and critical failures; silence successes only.
* Send plugin and theme digests only when an item in the run failed.
* Silence the email announcing a new version of WordPress.
* Disable the six-monthly admin-email verification screen.

= 1.0.0 =
* Silence plugin, theme and debug update email.

== Upgrade Notice ==

= 3.1.1 =
Corrects the plugin and theme counts in the status panel. No settings change.

= 3.1.0 =
Adds a status panel at the top of the settings screen. Nothing else changes,
and no setting of yours is touched.

= 3.0.0 =
Settings are new, and everything starts switched off. Version 2.0.0 acted on
activation; this one waits to be told, so visit Settings > Quiet Updates after
upgrading or no mail will be silenced.

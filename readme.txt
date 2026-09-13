=== Quiet Update Emails ===
Contributors: mattgregorydev
Tags: updates, email, notifications, maintenance
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Silences routine WordPress update mail and the periodic admin-email check, while letting failures through.

== Description ==

WordPress emails after every automatic update, whether or not anything went
wrong. Across a handful of sites that is enough mail to stop being read, which
is what makes the one that matters easy to miss.

This plugin keeps the failures and drops the rest, so the inbox goes back to
meaning something needs attention.

**Silenced**

* Core auto-update success
* Plugin and theme auto-update runs where every item succeeded
* The "WordPress x.y is available" nudge
* The automatic-update debug email
* The admin-email verification screen shown at login every six months

**Still sent**

* Core auto-update failures
* Core auto-update *critical* failures, where the site may be down
* Plugin or theme runs where at least one item failed

Nothing about update *policy* changes. Which updates install, and whether they
install automatically, stay where you set them.

There are no settings.

== Frequently Asked Questions ==

= Will I still know an update is available? =

Yes. The dashboard and the Updates screen are untouched; only the email is
suppressed.

= Does this stop automatic updates? =

No. It changes which emails you receive, nothing else. Update behavior is
controlled by the WP_AUTO_UPDATE_CORE constant and the per-plugin auto-update
settings, neither of which this plugin reads or writes.

= Why keep the failure emails? =

A 'critical' core update failure means the site may be down. That is the one
message worth an interruption, and a blanket suppression would discard it along
with the routine mail.

== Changelog ==

= 2.0.0 =
* Keep core update failures and critical failures; silence successes only.
* Send plugin and theme digests only when an item in the run failed.
* Silence the "WordPress x.y is available" email.
* Disable the six-monthly admin-email verification screen.

= 1.0.0 =
* Silence plugin, theme and debug update email.

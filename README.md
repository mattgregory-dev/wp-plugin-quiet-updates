# Quiet Update Emails

Silences the routine WordPress update mail you choose to silence, and keeps the
rest.

WordPress emails after every automatic update, whether or not anything went
wrong. Across a handful of sites that is enough mail to stop being read — which
is what makes the one that matters easy to miss.

**Nothing is silenced until you choose it.** A fresh activation changes no mail
at all. Everything is configured under **Settings → Quiet Updates**.

## What it can silence

Core, plugin and theme update mail each get the same three-way choice:

| Choice | Effect |
|---|---|
| Send every email | WordPress behaves normally |
| Silence successes | Routine mail stops, failures still arrive — **recommended** |
| Silence everything | Failures included |

Plus three independent toggles: the new-version-available email, the
admin-email verification screen that interrupts a login every six months, and
the automatic-update debug email.

Update *policy* is untouched. Which updates install, and whether they install
automatically, stays where you set it.

## What it shows you

At the top of the settings screen, a read-only summary of what your site
actually does with automatic updates: whether core installs every release, only
minor and security ones, or nothing, plus how many plugins and themes update
themselves. When a host or another plugin controls core updates, it says so and
offers no link, because the setting would be unchangeable on the other end.

Nothing in that panel changes a setting. WordPress owns the switch.

## Installing

Download the zip from [Releases](https://github.com/mattgregory-dev/wp-plugin-quiet-updates/releases)
and install it through **Plugins → Add New → Upload Plugin**.

Do not use GitHub's green "Download ZIP" button. It produces a folder named
`quiet-updates-main`, and WordPress identifies a plugin by its folder — the
result installs as a different plugin from the one the releases carry, so it
will not upgrade in place.

## Requirements

WordPress 6.9+, PHP 7.4+. Single site; there is no network-admin screen.

## Development

No build step. It is plain PHP — clone it into `wp-content/plugins/` and
activate.

The decision logic is separated from the filters so it can be exercised without
a real update: `quiet_updates_decide_core()` and `quiet_updates_decide_bulk()`
take their mode as an argument rather than reading the option.

## License

GPLv2 or later.

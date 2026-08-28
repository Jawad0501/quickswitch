=== QuickSwitch ===
Contributors: jawad0501
Tags: user switching, development, testing, roles, capabilities
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pin test users and switch between WordPress accounts in one click while developing plugins.

== Description ==

QuickSwitch helps plugin developers test role-based behavior without repeatedly hunting through the Users screen.

When you are building a plugin, you often need to jump between an admin account and a handful of fixed test users (Editor, Subscriber, Customer, etc.). Existing user switching plugins work, but they still make you find the same accounts in the user list every time. QuickSwitch adds a pinning layer on top of a complete, standalone switching implementation.

= Switching =

* **Switch To** — log in as another user instantly
* **Switch Back** — return to your original account
* **Switch Off** — log out while keeping the ability to switch back
* Standalone auth-cookie and session-token implementation (no dependency on User Switching or similar plugins)
* Smart redirects after switching — if the target user cannot access the page you were on, they are sent to the dashboard or homepage instead of an unauthorized error

= Pinning =

* Pin or unpin users from the **Users** list row actions
* Pins are stored per admin in usermeta (no custom database tables)
* Manage pinned users from **Users → QuickSwitch**

= Admin bar panel =

* **Switch User** menu on all wp-admin screens
* **Pinned** and **All users** tabs
* Search all users by username or email
* Infinite scroll loading (20 users at a time)
* Each row shows avatar, role, login, and a **Switch To** action
* Fixed-height, scrollable panel (no full-screen dropdown)

= Profile & user screens =

* **Switch To** button on every user edit screen (next to **Add User**)
* **Switch To** button in the Personal Options section of user profiles

= Permissions =

Users with the `edit_users` capability can pin users and switch accounts. Switch back uses cookie authentication and does not require the switched-in user to have elevated capabilities.

QuickSwitch is intentionally small: no audit log, no multisite-specific code, no CLI, no REST API layer, and no dependency on other switching plugins.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/quickswitch`, or install through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Users**, pin your test accounts using the **Pin** row action.
4. During testing, open **Switch User** from the admin bar and click a pinned account.

== Frequently Asked Questions ==

= Who can use QuickSwitch? =

Users with the `edit_users` capability can pin users, open the Switch User panel, and switch into other accounts.

= Does this replace User Switching? =

Yes. QuickSwitch is standalone. It implements its own switch-to, switch-back, and switch-off flow using WordPress auth cookies and session tokens, so you do not need another switching plugin installed.

= What happens if I switch to a user who cannot access the current admin page? =

QuickSwitch checks whether the switched-in user can access the page you were on. If not, they are redirected to the admin dashboard (if they have admin access) or the site homepage — not an "Sorry, you are not allowed" error screen.

= Are pins shared between admins? =

No. Pins are stored against your own user account and are visible only to you.

= Does QuickSwitch work on Multisite? =

Multisite is not a priority for v1. It may work incidentally but is not tested or supported.

== Screenshots ==

1. Admin bar Switch User panel with Pinned and All users tabs
2. Users → QuickSwitch settings page
3. Switch To button on user edit screen

== Changelog ==

= 1.0.6 =
* Place Switch To header button to the right of Add User on user edit screens

= 1.0.5 =
* Add Switch To button on user profile edit screens (header and Personal Options table)

= 1.0.4 =
* Redirect switched-in users to a safe fallback when they cannot access the current admin page

= 1.0.3 =
* Fix expired nonce / "link has expired" errors on switch links from the admin bar panel

= 1.0.2 =
* Clear list immediately on tab switch and search; show loading state only while fetching

= 1.0.1 =
* Replace native admin bar submenu with custom scrollable panel (tabs, search, infinite scroll)
* Redesign Users → QuickSwitch settings page empty state and pin table
* Fix admin bar layout conflicts with WordPress core styles

= 1.0.0 =
* Initial release: standalone switching, pinning, admin bar menu, settings page, Users list row actions

== Upgrade Notice ==

= 1.0.6 =
Switch To button on user edit screens now appears next to Add User.

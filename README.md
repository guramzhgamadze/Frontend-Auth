# WP Frontend Auth

Secure, accessible frontend login, registration, and password recovery forms for WordPress — with rate limiting, honeypot protection, AJAX support, and native Elementor widgets.

## Description

WP Frontend Auth replaces the default `wp-login.php` experience with clean, theme-integrated forms that live on your actual site. It works out of the box on any WordPress theme and ships with first-class Elementor support — four drag-and-drop widgets that fit into any page builder layout with full Theme Builder compatibility.

### What It Does

- **Login form** with username, email, or either — configurable from Settings.
- **Registration form** with optional user-chosen passwords and auto-login.
- **Lost Password / Reset Password** forms with full email flow integration.
- **URL rewriting** — all `wp-login.php` links site-wide are transparently redirected to your frontend pages.
- **Multisite support** — network-activated, per-site settings, signup/activation flow handled.

### Security

- **Nonce verification** on every form submission.
- **Rate limiting** — configurable max attempts per IP with lockout window (uses transients). Applied to all four handlers: login, register, lost-password, and reset-password.
- **Honeypot spam protection** — rotating hidden field (hourly key rotation via HMAC) catches bots. Trapped submissions get a fake success response — bots never know they failed.
- **IP anonymisation** — rate-limit keys hash truncated IPs (last octet zeroed for IPv4, /48 for IPv6). Defaults to `REMOTE_ADDR` only — forwarded headers require explicit opt-in via the `wpfa_rate_limit_ip_headers` filter.
- **No password pre-population** — password fields are never re-filled from POST data.
- **bcrypt-compatible** — uses `wp_set_password()` / `wp_signon()` which support WP 6.8+ bcrypt hashing.
- **Password minimum length** — reset and registration passwords require at least 8 characters.

### Elementor Integration

Four native `Widget_Base` widgets registered via `elementor/widgets/register`:

| Widget | Class | Description |
|--------|-------|-------------|
| Login Form | `WPFA_Elementor_Login_Widget` | Login form with custom labels, placeholders, toggle text, and link overrides. Hidden when logged in (unless `reauth=1`). |
| Registration Form | `WPFA_Elementor_Register_Widget` | Registration form with password + confirm fields when user-chosen passwords are enabled. Editor placeholder when registration is disabled. |
| Lost Password Form | `WPFA_Elementor_Lost_Password_Widget` | Password recovery request form. |
| Reset Password Form | `WPFA_Elementor_Reset_Password_Widget` | Password reset form — reads `?key=&login=` from the URL. Shows invalid-link message when parameters are missing, with an editor preview of the form fields. |

All widgets share a comprehensive Elementor style panel: form container (width, max-width, alignment, background, border, radius, shadow, padding), title typography, label styling, input fields (text colour, placeholder colour, background, border, focus state with glow), button (normal + hover tabs with typography, padding, radius, shadow, transition), action links, messages/errors, password toggle (normal + hover tabs), and checkbox styling.

Only the Reset Password widget declares `is_dynamic_content(): true` (it reads `$_GET` parameters). The other three return `false` for optimal Elementor caching.

#### Page Management

The plugin includes a **Page Management** panel in the settings screen. You can manually create real WordPress pages for each auth action so Elementor Theme Builder conditions work correctly (Singular > Page targeting by ID). Pages are **not** created automatically on activation — you choose when and whether to create them. The plugin works without real pages via its virtual URL rewrite system.

### Classic Widgets

Four `WP_Widget` subclasses are also registered for classic sidebar/widget-area use:

- `WPFA_Login_Widget`
- `WPFA_Register_Widget`
- `WPFA_Lost_Password_Widget`
- `WPFA_Reset_Password_Widget`

All expose `show_instance_in_rest` for the WP 5.8+ block-based Widgets screen.

## Requirements

| Dependency | Minimum |
|-----------|---------|
| WordPress | 6.5+ |
| PHP | 8.0+ |
| Elementor | Optional — plugin works without it |

## Installation

1. Upload the `wp-frontend-auth` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Go to **Frontend Auth** in the admin sidebar to configure options.
4. *(Optional)* Click **Create Missing Pages** in the Page Management section to create real WordPress pages for Elementor Theme Builder targeting.
5. Visit **Settings → Permalinks** and click **Save Changes** to flush rewrite rules (or run `wp rewrite flush`).
6. *(Elementor users)* Open any page in the Elementor editor and search for "Login Form", "Registration Form", etc. in the widget panel under the **Frontend Auth** category.

## Settings

All settings are under the **Frontend Auth** admin menu:

### General

| Setting | Default | Description |
|---------|---------|-------------|
| Login with | Username or Email | Restrict to username-only or email-only |
| Pretty URLs | On | Uses `/login/` instead of `?action=login` |
| AJAX forms | Off | Submit forms without page reload |
| User-chosen passwords | Off | Shows password fields on registration form |
| Auto-login | Off | Logs users in immediately after registering |
| Honeypot protection | On | Hidden field to catch bots |

### Rate Limiting

| Setting | Default | Description |
|---------|---------|-------------|
| Max attempts | 10 | Failed attempts before lockout (0 = disabled) |
| Lockout window | 15 min | Duration of lockout after max attempts reached |

### Page Slugs

Each action URL slug is customisable: `login`, `logout`, `register`, `lostpassword` (default: `lost-password`), `resetpass` (default: `reset-password`).

### Page Management

| Button | Description |
|--------|-------------|
| Create Missing Pages | Creates real WordPress pages for any auth action that doesn't already have one. Existing pages with matching slugs are adopted, not duplicated. |
| Delete Auto-Created Pages | Removes only pages the plugin created. Pages you created manually and the plugin adopted are left intact. |

## Hooks & Filters

### Actions

| Hook | Parameters | Description |
|------|-----------|-------------|
| `wpfa_init` | `WPFA $instance` | Fires when the core class initialises |
| `wpfa_registered_action` | `string $name, array $args` | After an action is registered |
| `wpfa_registered_form` | `string $name, WPFA_Form $form` | After a form is registered |
| `wpfa_before_form_{name}` | `WPFA_Form $form` | Before form HTML renders |
| `wpfa_after_form_{name}` | `WPFA_Form $form` | After form HTML renders |
| `wpfa_{name}_form` | — | Inside form, for adding custom fields |
| `wpfa_action_{action}` | — | Fires when a POST action is dispatched |
| `wpfa_login_failed` | `string $username` | After a failed login attempt |
| `wpfa_login_success` | `WP_User $user` | After successful login |
| `wpfa_logout_success` | — | After successful logout |
| `wpfa_registration_success` | `int $user_id` | After successful registration |
| `wpfa_password_reset` | `WP_User $user` | After successful password reset |
| `wpfa_rate_limit_recorded` | `string $action, int $attempts` | After a rate-limit bump |

### Filters

| Filter | Default | Description |
|--------|---------|-------------|
| `wpfa_use_permalinks` | `true` | Toggle pretty URLs |
| `wpfa_use_ajax` | `false` | Toggle AJAX form submission |
| `wpfa_allow_user_passwords` | `false` | Toggle user-chosen passwords |
| `wpfa_allow_auto_login` | `false` | Toggle auto-login after registration |
| `wpfa_use_honeypot` | `true` | Toggle honeypot protection |
| `wpfa_rate_limit` | `10` | Max failed attempts |
| `wpfa_rate_limit_window` | `15` | Lockout window in minutes |
| `wpfa_rate_limit_ip_headers` | `['REMOTE_ADDR']` | `$_SERVER` keys checked for client IP (add forwarded headers only behind a verified proxy) |
| `wpfa_action_url` | — | Filter any action URL |
| `wpfa_action_slug_{action}` | — | Filter a specific action's slug |
| `wpfa_username_label` | — | Filter the username field label |
| `wpfa_logged_in_redirect` | Role-based: `home_url()` for subscribers, `admin_url()` for privileged roles | Redirect URL for logged-in users hitting login/register |
| `wpfa_logout_redirect` | `home_url()` | Redirect URL after logout |
| `wpfa_login_url_exempt` | `false` | Return `true` to bypass WPFA's login URL rewriting (for OAuth/MCP flows) |
| `wpfa_script_data` | — | Filter the JS config object |
| `wpfa_form_links_{name}` | — | Filter action links below a form |
| `wpfa_form_attributes_{name}` | — | Add custom HTML attributes to a form |
| `wpfa_widget_form_output` | — | Filter rendered form HTML |
| `wpfa_new_user_notification` | `'both'` | Control new-user email recipients |
| `wpfa_page_actions` | — | Filter which actions get real WP pages |
| `wpfa_ajax_success_data` | — | Filter AJAX success response data |
| `wpfa_ajax_error_data` | — | Filter AJAX error response data |

## 3rd-Party Plugin Compatibility

WP Frontend Auth fires the standard WordPress form hooks (`login_form`, `register_form`, `lostpassword_form`, `resetpass_form`) inside its forms. This means plugins that add fields to WordPress's native login — including 2FA plugins, CAPTCHA plugins, and social login plugins — will render their fields inside WPFA forms automatically.

An OAuth/REST exemption system is also built in: when another plugin (e.g. WordPress MCP Bridge) calls `wp_login_url()` with a REST API redirect target, WPFA automatically stands aside and returns the native `/wp-login.php` URL. Plugins can also use the `wpfa_login_url_exempt` filter for explicit opt-out.

## File Structure

```
wp-frontend-auth/
├── wp-frontend-auth.php          Main plugin file (activation, deactivation, Elementor loader)
├── uninstall.php                 Cleanup on deletion (respects user-created pages)
├── README.md                     This file
├── index.html                    GitHub Pages landing page
├── admin/
│   ├── settings.php              Settings page with card-based UI
│   └── hooks.php                 Admin hooks, slug sync, page management handlers
├── assets/
│   ├── scripts/
│   │   ├── wp-frontend-auth.js   Frontend JS (AJAX, password toggle, strength meter)
│   │   └── wp-frontend-auth.min.js
│   └── styles/
│       ├── wp-frontend-auth.css       Frontend CSS (CSS custom properties, V4 compatible)
│       ├── wp-frontend-auth.min.css
│       └── wp-frontend-auth-editor.css  Elementor editor-only styles
├── includes/
│   ├── class-wpfa.php            Core singleton (actions & forms registry)
│   ├── class-wpfa-form.php       Form class (fields, rendering, errors)
│   ├── options.php               Option accessors, page management, slug helpers
│   ├── helpers.php               Request helpers, URL helpers, honeypot, Elementor detection
│   ├── handlers.php              Form POST handlers (login, register, lostpassword, resetpass)
│   ├── hooks.php                 Frontend hooks, rewrites, URL filters, virtual pages
│   ├── forms.php                 Form definitions (field registration, link filters)
│   ├── widgets.php               Classic WP_Widget classes (4 widgets)
│   ├── rate-limit.php            Rate limiting via transients
│   ├── ms-hooks.php              Multisite-specific hooks
│   └── elementor/
│       └── class-wpfa-elementor-widgets.php   Elementor Widget_Base classes (4 widgets)
└── languages/
    └── .gitkeep
```

## Changelog

### 1.4.14

**Bug Fixes**

- **High (Security):** Fixed PHP 8.0+ fatal `TypeError` crash via array-valued HTTP parameters. An attacker could send `log[]=foo`, `key[]=bar`, or any other array-formatted parameter to crash form handlers — `sanitize_user()`, `sanitize_text_field()`, `sanitize_key()`, and `wp_sanitize_redirect()` all expect strings and throw a fatal `TypeError` when given arrays on PHP 8.0+. This was a denial-of-service vector that bypassed nonce verification (the crash occurred after the nonce check passed). Fixed across 7 files by adding `is_string()` guards to all direct `$_GET`/`$_POST`/`$_REQUEST` access points, and by changing the core `wpfa_get_request_value()` helper to return an empty string for non-string input instead of passing raw arrays through.
- **Medium (Security):** Added missing honeypot check to the lost-password handler. The honeypot hidden field was rendered in the form HTML but `wpfa_honeypot_is_spam()` was never called in `wpfa_handle_lostpassword()`. This allowed bots to automate the lost-password form and trigger mass password-reset emails to arbitrary users. The handler now checks the honeypot before calling `retrieve_password()` and returns a fake success response to fool the bot — identical to the existing pattern in the registration handler.
- **Low:** Replaced deprecated `wp.passwordStrength.userInputBlacklist()` with `wp.passwordStrength.userInputDisallowedList()` in the password strength meter JavaScript. The old API was deprecated in WordPress 5.5.0 (Trac #50413) and logged a console warning on every keystroke. Since the plugin requires WP 6.5+, the replacement API is guaranteed available. Fixed in both the source and minified JS files.
- **Low:** Fixed `wpfa_rate_limit_clear()` not deleting the companion `_ts` timestamp transient alongside the counter. After a successful login, the orphaned `_ts` transient caused `wpfa_rate_limit_remaining_seconds()` to return a stale non-zero lockout duration even though the user was no longer locked out — misleading any theme or plugin using the public API to display retry timers.

### 1.4.13

**Bug Fixes**

- **High:** Fixed login redirect sending all roles (including admins, editors, authors, contributors) to `home_url()` or ignoring the `redirect_to` parameter. Previously, `wpfa_maybe_redirect_logged_in_user()` blindly redirected every logged-in user visiting the login/register page to `admin_url()`, ignoring the `redirect_to` query parameter entirely. Now, the `redirect_to` parameter is honoured for privileged roles, and only subscribers are redirected away from `wp-admin` (to `home_url()` instead).
- **High:** Fixed login handler (`wpfa_handle_login`) also using `admin_url()` as the default redirect for subscribers. Subscribers now default to `home_url()` on login. If a subscriber's `redirect_to` points to `wp-admin`, it is overridden to `home_url()`.

**Documentation Fixes**

- Fixed incorrect inline comment claiming `wp_send_new_user_notification_to_admin` was introduced in WP 4.6 — the correct version is WP 6.1.0 (per `@since 6.1.0` in WordPress core source).
- Fixed misleading comment in main plugin file referencing `load_plugin_textdomain()` as "soft-deprecated in WP 6.7" — it was not deprecated but made redundant by the deferred translation loading system.
- Fixed WP version guard comment inconsistency (referenced "6.2+ minimum" when the actual requirement is 6.5+).
- Updated `wpfa_logged_in_redirect` filter documentation to reflect the new role-based default (`home_url()` for subscribers, `admin_url()` for privileged roles).

### 1.4.12

**Bug Fixes**

- **Critical:** Removed automatic page creation on plugin activation/reactivation. Previously, deactivating and reactivating the plugin created duplicate Login, Register, Lost Password, and Reset Password pages every cycle. Pages are now managed manually via a new **Page Management** panel in the settings screen with "Create Missing Pages" and "Delete Auto-Created Pages" buttons.
- **Critical:** Fixed `render_form_title()` in Elementor widgets — `add_render_attribute()` was called with the return value of `get_render_attribute_string()` as the attribute name, producing malformed HTML on every widget with a form title.
- **High:** Fixed password toggle click listeners stacking on Elementor pages. `document.addEventListener('click', ...)` was inside `bindPasswordToggle()` which runs on every Elementor `element_ready` re-render. After N renders, N+1 identical listeners caused rapid toggle flicker. Moved to a single document-level delegate registered once at boot.
- **High:** Fixed Elementor `element_ready` hooks never registering. Used native `addEventListener` for `elementor/frontend/init` but Elementor fires this via jQuery's event system. Changed to `jQuery(window).on(...)`.
- **Medium:** Fixed `uninstall.php` deleting user-created pages. Now tracks auto-created pages via `_wpfa_auto_created` post meta and only deletes those.
- **Low:** Corrected `Group_Control_Box_Shadow` comments incorrectly stating it is "Pro-only" — it is available in free Elementor.

### 1.4.11

- Fixed double admin notification email on registration when user-chosen passwords are enabled.
- Fixed hardcoded `post_author => 1` in auto-created pages.
- Fixed IP address spoofing in rate limiter — defaults to `REMOTE_ADDR` only.
- Added rate limiting to the reset-password handler.
- Added `reauth=1` support for re-authentication without redirect loops.
- Fixed OAuth/REST exemption for login URL rewriting (MCP Bridge compatibility).

### 1.4.8

- Fixed triple-brace in placeholder HTML attributes in Elementor content templates.
- Wired `bindPasswordToggle()` and `bindPasswordStrength()` to Elementor `element_ready` lifecycle.
- Replaced `outline:none` with `:focus/:focus-visible` pair (WCAG 2.2).
- Added `Group_Control_Typography` for error/success messages, Remember Me, and strength meter.
- Added text-decoration control for action links.
- Renamed heading control IDs to `wpfa_h_*` to avoid cross-widget collision.

### 1.4.3

- Fixed `const wpFrontendAuth` declared twice causing SyntaxError on Elementor pages.
- Fixed Elementor editor filter leak — closures inside `render()` now cleaned up immediately.

### 1.4.0

- Real WordPress pages for auth actions (Elementor Theme Builder compatibility).
- Full Elementor style panel with 15+ control sections.
- Custom label, placeholder, button text, and link URL overrides per widget instance.
- Password toggle with per-field Show/Hide text via data attributes.
- Form self-posting for reliable AJAX on Elementor pages.
- Elementor V4 Atomic Widgets compatibility (`has_widget_inner_wrapper(): false`).

### 1.2.0

- Initial public release.

## License

GPL-2.0-or-later — https://www.gnu.org/licenses/gpl-2.0.html

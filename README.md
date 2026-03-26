# WP Frontend Auth

Secure, accessible frontend login, registration, and password recovery forms for WordPress — with rate limiting, honeypot protection, AJAX support, and native Elementor widgets.

## Description

WP Frontend Auth replaces the default `wp-login.php` experience with clean, theme-integrated forms that live on your actual site. It works out of the box on any WordPress theme and ships with first-class Elementor support — five drag-and-drop widgets that fit into any page builder layout with full Theme Builder compatibility.

### What It Does

- **Login form** with username, email, or either — configurable from Settings.
- **Registration form** with optional user-chosen passwords and auto-login.
- **Lost Password / Reset Password** forms with full email flow integration.
- **Logged-in dashboard** widget showing avatar, greeting, and quick links.
- **URL rewriting** — all `wp-login.php` links site-wide are transparently redirected to your frontend pages.
- **Multisite support** — network-activated, per-site settings, signup/activation flow handled.

### Security

- **Nonce verification** on every form submission.
- **Rate limiting** — configurable max attempts per IP with lockout window (uses transients).
- **Honeypot spam protection** — rotating hidden field catches bots, silently fakes success.
- **IP anonymisation** — rate-limit keys hash truncated IPs (last octet zeroed for IPv4, /48 for IPv6).
- **No password pre-population** — password fields are never re-filled from POST data.
- **bcrypt-compatible** — uses `wp_set_password()` / `wp_signon()` which support WP 6.8+ bcrypt hashing.

### Elementor Integration

Five native `Widget_Base` widgets registered via `elementor/widgets/register`:

| Widget | Class | Description |
|--------|-------|-------------|
| Login Form | `WPFA_Elementor_Login_Widget` | Login form; shows dashboard when logged in |
| Registration Form | `WPFA_Elementor_Register_Widget` | Registration form; editor placeholder when disabled |
| Lost Password Form | `WPFA_Elementor_Lost_Password_Widget` | Password recovery request form |
| Reset Password Form | `WPFA_Elementor_Reset_Password_Widget` | Password reset form (reads `?key=&login=` from URL) |
| Auth Dashboard | `WPFA_Elementor_Dashboard_Widget` | Logged-in panel; optional login form when logged out |

All widgets declare `is_dynamic_content(): true` to disable Elementor output caching (forms contain nonces and user-specific state) and `has_widget_inner_wrapper(): false` for V4 compatibility.

On activation, the plugin creates real WordPress pages (`/login/`, `/register/`, `/lost-password/`, `/reset-password/`) so Elementor Theme Builder conditions work correctly. No virtual post hacks needed.

### Classic Widgets

Five `WP_Widget` subclasses are also registered for classic sidebar/widget-area use:

- `WPFA_Login_Widget`
- `WPFA_Register_Widget`
- `WPFA_Lost_Password_Widget`
- `WPFA_Reset_Password_Widget`
- `WPFA_Dashboard_Widget`

All expose `show_instance_in_rest` for the WP 5.8+ block-based Widgets screen.

## Requirements

| Dependency | Minimum |
|-----------|---------|
| WordPress | 6.9+ |
| PHP | 8.1+ |
| Elementor | Optional — plugin works without it |

## Installation

1. Upload the `wp-frontend-auth` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Go to **Settings → Frontend Auth** to configure options.
4. Visit **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.
5. *(Elementor users)* Open any page in the Elementor editor and search for "Login Form", "Registration Form", etc. in the widget panel.

## Settings

All settings are under **Settings → Frontend Auth**:

### General

| Setting | Default | Description |
|---------|---------|-------------|
| Login with | Username or Email | Restrict to username-only or email-only |
| Use pretty URLs | On | Uses `/login/` instead of `?action=login` |
| AJAX forms | Off | Submit forms without page reload |
| Allow users to set own password | Off | Shows password fields on registration form |
| Auto-login after registration | Off | Logs users in immediately after registering |
| Honeypot spam protection | On | Hidden field to catch bots |

### Rate Limiting

| Setting | Default | Description |
|---------|---------|-------------|
| Max attempts | 10 | Failed attempts before lockout (0 = disabled) |
| Lockout window | 15 min | Duration of lockout after max attempts reached |

### Page Slugs

Each action URL slug is customisable: `login`, `logout`, `register`, `lost-password`, `reset-password`, `dashboard`.

## Hooks & Filters

### Actions

| Hook | Parameters | Description |
|------|-----------|-------------|
| `wpfa_init` | `WPFA $instance` | Fires when the core class initializes |
| `wpfa_before_form_{name}` | `WPFA_Form $form` | Before form HTML renders |
| `wpfa_after_form_{name}` | `WPFA_Form $form` | After form HTML renders |
| `wpfa_{name}_form` | — | Inside form, for adding custom fields |
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
| `wpfa_action_url` | — | Filter any action URL |
| `wpfa_action_slug_{action}` | — | Filter a specific action's slug |
| `wpfa_username_label` | — | Filter the username field label |
| `wpfa_logged_in_redirect` | `admin_url()` | Redirect URL for logged-in users hitting login/register |
| `wpfa_logout_redirect` | `home_url()` | Redirect URL after logout |
| `wpfa_script_data` | — | Filter the JS config object |
| `wpfa_dashboard_links` | — | Filter dashboard widget links |
| `wpfa_form_links_{name}` | — | Filter action links below a form |
| `wpfa_form_attributes_{name}` | — | Add custom HTML attributes to a form |
| `wpfa_widget_form_output` | — | Filter rendered form HTML |
| `wpfa_new_user_notification` | `'both'` | Control new-user email recipients |
| `wpfa_page_actions` | — | Filter which actions get real WP pages |
| `wpfa_ajax_success_data` | — | Filter AJAX success response data |
| `wpfa_ajax_error_data` | — | Filter AJAX error response data |

## 3rd-Party Plugin Compatibility

WP Frontend Auth fires the standard WordPress form hooks (`login_form`, `register_form`, `lostpassword_form`, `resetpass_form`) inside its forms. This means plugins that add fields to WordPress's native login — including 2FA plugins, CAPTCHA plugins, and social login plugins — will render their fields inside WPFA forms automatically.

## File Structure

```
wp-frontend-auth/
├── wp-frontend-auth.php          Main plugin file
├── uninstall.php                 Cleanup on deletion
├── README.md                     This file
├── index.html                    GitHub Pages promo site
├── admin/
│   ├── settings.php              Settings page & field renderers
│   └── hooks.php                 Admin-only hooks
├── assets/
│   ├── scripts/
│   │   ├── wp-frontend-auth.js   Frontend JS (AJAX, password toggle, strength meter)
│   │   └── wp-frontend-auth.min.js
│   └── styles/
│       ├── wp-frontend-auth.css  Frontend CSS
│       └── wp-frontend-auth.min.css
├── includes/
│   ├── class-wpfa.php            Core singleton (actions & forms registry)
│   ├── class-wpfa-form.php       Form class (fields, rendering, errors)
│   ├── options.php               Option accessors, page management, slug helpers
│   ├── helpers.php               Request helpers, URL helpers, honeypot, Elementor detection
│   ├── handlers.php              Form POST handlers (login, register, lostpassword, resetpass)
│   ├── hooks.php                 Frontend hooks, rewrites, URL filters, virtual pages
│   ├── forms.php                 Form definitions (field registration)
│   ├── widgets.php               Classic WP_Widget classes
│   ├── rate-limit.php            Rate limiting via transients
│   ├── ms-hooks.php              Multisite-specific hooks
│   └── elementor/
│       └── class-wpfa-elementor-widgets.php   Elementor Widget_Base classes
└── languages/
    └── .gitkeep
```

## Changelog

### 1.2.1

**Bug Fixes**

- **Critical:** Fixed wrong filter hook for suppressing user notification emails when user-chosen passwords are enabled. The plugin was hooking `wp_send_new_user_notifications` (a function name, not a filter). Changed to the correct `wp_send_new_user_notification_to_user` filter (WP 6.1+). Without this fix, users who set their own password during registration still received the "click here to set your password" email — which was incorrect and confusing.
- **Medium:** Added missing CSS styles for `.wpfa-password-toggle` button. The password show/hide toggle rendered as an unstyled browser-default button.
- **Medium:** `retrieve_password()` call in lost-password handler now uses the `wpfa_get_request_value()` helper for consistency with other handlers (was previously accessing `$_POST` directly).
- **Low:** Focus accessibility improvement — `:focus` styles now use `outline: 2px solid transparent` instead of `outline: none` so browsers without `:focus-visible` support still show a visible focus ring via `box-shadow`.
- **Low:** Added `typeof wpFrontendAuth` guard in the frontend JS to prevent `ReferenceError` if the inline config script fails to output.
- **Low:** Minified assets (`.min.css`, `.min.js`) are now properly minified (47% and 54% size reduction respectively). Previously these files were identical or near-identical to the unminified versions.

### 1.2.0

- Initial public release.
- Login, registration, lost-password, and reset-password forms.
- Elementor Widget_Base widgets (5) and classic WP_Widget widgets (5).
- Rate limiting, honeypot protection, AJAX support.
- Multisite support with network activation.
- Real WordPress pages created on activation for Elementor Theme Builder compatibility.
- URL rewriting for all `wp-login.php` references.
- Customisable slugs, login-type enforcement, user-chosen passwords, auto-login.

## License

GPL-2.0-or-later — https://www.gnu.org/licenses/gpl-2.0.html

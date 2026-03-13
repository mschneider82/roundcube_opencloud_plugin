# roundcube-opencloud-plugin

A Roundcube plugin that lets each user connect their personal [OpenCloud](https://opencloud.eu) Space to Roundcube — save email attachments directly to the cloud or attach files from it.

This is a fork of [nicofrand/Roundav](https://github.com/nicofrand/Roundav), which itself is a fork of [Roundcube-Plugin-roundav](https://github.com/messagerie-melanie2/Roundcube-Plugin-roundav).

**Packagist:** https://packagist.org/packages/mschneider82/roundcube-opencloud
**Repository:** https://github.com/mschneider82/roundcube_opencloud_plugin

> **Related:** [opencloud-webmail](https://github.com/mschneider82/opencloud-webmail) — OpenCloud web extension that embeds Roundcube directly inside the OpenCloud UI via iframe with HMAC autologin.
>
> **Article:** [From Seafile to OpenCloud: Building a Self-Hosted Webmail & Cloud Integration on Kubernetes](https://medium.com/@matthias2handy/from-seafile-to-opencloud-building-a-self-hosted-webmail-cloud-integration-on-kubernetes-a4f3bb795d6f) — background, motivation, and full setup walkthrough.

## Features

- Save email attachments to your personal OpenCloud Space
- Attach files from your OpenCloud Space when composing emails
- Each user configures their own Space — no shared credentials needed
- Supports OpenCloud Spaces WebDAV URLs (including Space IDs with `$`)

## Requirements

- Roundcube 1.6+
- PHP 8.1+
- An [OpenCloud](https://opencloud.eu) instance

## Installation

### Docker (roundcube/roundcubemail image)

Set these environment variables:

```env
ROUNDCUBEMAIL_PLUGINS=archive,zipdownload,roundcube_opencloud
ROUNDCUBEMAIL_COMPOSER_PLUGINS=mschneider82/roundcube-opencloud
ROUNDCUBEMAIL_INSTALL_PLUGINS=1
```

### Composer

```bash
composer require mschneider82/roundcube-opencloud
```

Then add `roundcube_opencloud` to `$config['plugins']` in your Roundcube config.

### Manual

1. Place this plugin folder into the `plugins/` directory of Roundcube and rename it to `roundcube_opencloud`.
2. Run `composer install --no-dev` inside the plugin folder.
3. Add `roundcube_opencloud` to `$config['plugins']` in your Roundcube config.

## Configuration

The plugin works out of the box — all settings are optional. To override global defaults, edit `config.inc.php`:

```php
// Show the "Files" tab in the taskbar (default: true)
$rcmail_config['show_drive_task'] = true;

// Optional: set a global fallback Spaces URL (users can override this in their settings)
$rcmail_config['driver_webdav_spaces_url'] = null;

// Optional: set global WebDAV credentials (not recommended — use per-user settings instead)
$rcmail_config['driver_webdav_username'] = null;
$rcmail_config['driver_webdav_password'] = null;
```

## User Setup

Each user configures their own OpenCloud Space in Roundcube under **Settings → Cloud Storage**:

### 1. Create an App Password in OpenCloud

Regular OpenCloud passwords do not work for WebDAV. You need to create an app password:

1. Log in to OpenCloud
2. Go to **Settings → Security**
3. Under **App Passwords**, create a new password and copy it

### 2. Find your Spaces WebDAV URL

1. In OpenCloud, go to **Settings** and enable **"Show WebDAV information in the detail view"**
2. Open the Space you want to use and click on its properties/details
3. Copy the WebDAV URL — it looks like:
   ```
   https://opencloud.example.com/remote.php/dav/spaces/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx$xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
   ```

### 3. Configure in Roundcube

1. In Roundcube, go to **Settings → Cloud Storage**
2. Enter your OpenCloud **username**
3. Enter the **app password** you created (not your regular password)
4. Paste the **Spaces WebDAV URL**
5. Save

You can now attach files from your Space or save attachments to it directly from the mail view.

## License

Released under the [GNU Affero General Public License Version 3](https://www.gnu.org/licenses/agpl-3.0.html).

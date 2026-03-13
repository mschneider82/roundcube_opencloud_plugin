<?php
// Show the drive task ?
$rcmail_config['show_drive_task'] = true;

// Full WebDAV Spaces URL for OpenCloud/ownCloud Spaces.
// Per-user Spaces URLs configured in Settings > Cloud Storage take precedence over this.
// The URL is parsed automatically into host and path — no manual splitting needed.
// Supports %u placeholder for the username.
// Example: 'https://opencloud.example.com/remote.php/dav/spaces/2a17da51-d0a2-4e8d-94e3-d4d1636bc265$386df95a-7c95-4aac-b442-fa0b8b20e190'
$rcmail_config['driver_webdav_spaces_url'] = null;

// Override the WebDAV username. Optional.
// If not set, per-user credentials from Settings > Cloud Storage are used.
// If neither are set, falls back to the Roundcube IMAP username.
$rcmail_config['driver_webdav_username'] = null;

// Override the WebDAV password. Optional.
// If not set, per-user credentials from Settings > Cloud Storage are used.
// If neither are set, falls back to the Roundcube IMAP password.
$rcmail_config['driver_webdav_password'] = null;

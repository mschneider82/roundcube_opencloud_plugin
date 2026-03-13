<?php

/**
 * Roundcube Drive using flysystem for filesystem
 *
 * @version @package_version@
 * @author Thomas Payen <thomas.payen@apitech.fr>
 *
 * This plugin is inspired by kolab_files plugin
 * Use flysystem library : https://github.com/thephpleague/flysystem
 * With flysystem WebDAV adapter : https://github.com/thephpleague/flysystem-webdav
 *
 * Copyright (C) 2015 PNE Annuaire et Messagerie MEDDE/MLETR
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/lib/roundcube_opencloud_plugin_files_engine.php');

class roundcube_opencloud_plugin extends rcube_plugin
{
    public const SESSION_FOLDERS_LIST_ID = 'roundcube_opencloud_plugin_folders_list';

    // All tasks excluding 'login' and 'logout'
    public $task = '?(?!login|logout).*';

    public $rc;
    public $home;
    private $engine;

    public function init()
    {
        $this->rc = rcube::get_instance();

        // Do not edit the order of the lines below.
        // Everything will break for some reason.

        $this->add_hook('refresh', array($this, 'refresh'));

        $this->register_action('plugin.roundav', array($this, 'actions'));

        $this->register_task('roundcube_opencloud_plugin');

        $this->register_action('index', array($this, 'actions'));
        $this->register_action('prefs', array($this, 'actions'));
        $this->register_action('open',  array($this, 'actions'));
        $this->register_action('file_api', array($this, 'actions'));

        $this->add_hook('startup', array($this, 'startup'));
        $this->add_hook('logout', array($this, 'onlogout'));

        // Settings hooks
        $this->add_hook('preferences_sections_list', array($this, 'preferences_sections'));
        $this->add_hook('preferences_list', array($this, 'preferences_list'));
        $this->add_hook('preferences_save', array($this, 'preferences_save'));
    }

    public function refresh($args = null)
    {
        $this->load_config();
        if (!$this->engine && roundcube_opencloud_plugin_files_engine::hasCredentials($this)) {
            $this->engine = new roundcube_opencloud_plugin_files_engine($this);
        }

        return $args;
    }

    /**
     * Startup hook handler, initializes/enables Files UI
     */
    public function startup($args)
    {
        $this->refresh();

        if ($this->rc->output->type != 'html') {
            return;
        }

        if ($this->engine) {
            $this->engine->ui($this);
        }

        return $args;
    }

    /**
     * Logout hook handler
     */
    public function onlogout($args)
    {
        unset($_SESSION[self::SESSION_FOLDERS_LIST_ID]);

        return $args;
    }

    /**
     * Engine actions handler
     */
    public function actions()
    {
        if ($this->engine)
        {
            $rc = rcube::get_instance();
            $rcTask = $rc->task;
            $rcAction = $rc->action;

            if ($rcTask == 'roundcube_opencloud_plugin' && $rcAction == 'file_api') {
                $action = rcube_utils::get_input_value('method', rcube_utils::INPUT_GPC);
            }
            else if ($rcTask == 'roundcube_opencloud_plugin' && $rcAction) {
                $action = $rcAction;
            }
            else if ($rcTask != 'roundcube_opencloud_plugin' && $_POST['act']) {
                $action = $_POST['act'];
            }
            else {
                $action = 'index';
            }

            switch ($action)
            {
                case 'index':
                    $this->engine->action_index($this);
                    break;

                case 'open';
                    $this->engine->action_open($this);
                    break;

                case 'save_file';
                    $this->engine->action_save_file($this);
                    break;

                case 'attach_file':
                    $this->engine->action_attach_file($this);
                    break;

                case 'folder_list':
                    $this->engine->action_folder_list($this);
                    break;

                case 'folder_create':
                    $this->engine->action_folder_create($this);
                    break;

                case 'file_list':
                    $this->engine->action_file_list($this);
                    break;

                case 'file_get':
                    $this->engine->action_file_get($this);
                    break;

                default:
                    echo(json_encode([
                        'status' => 'NOK',
                        'reason' => 'Unknown action',
                        'req_id' => rcube_utils::get_input_value('req_id', rcube_utils::INPUT_GET),
                    ]));
            }
        }
    }

    /**
     * Return attachment filename, handle empty filename case
     *
     * @param rcube_message_part $attachment Message part
     * @param bool               $display    Convert to a description text for "special" types
     *
     * @return string Filename
     */
    public function get_attachment_name($attachment, $display)
    {
        return rcmail_action_mail_index::attachment_name($attachment, $display);
    }

    /**
     * Register "Cloud Storage" section in Settings
     */
    public function preferences_sections($args)
    {
        $this->add_texts('localization/');

        $args['list']['roundcube_opencloud_plugin'] = array(
            'id'      => 'roundcube_opencloud_plugin',
            'section' => $this->gettext('settings_section'),
        );

        return $args;
    }

    /**
     * Render the Cloud Storage settings form
     */
    public function preferences_list($args)
    {
        if ($args['section'] != 'roundcube_opencloud_plugin') {
            return $args;
        }

        $this->add_texts('localization/');

        $username = $this->rc->config->get('roundcube_opencloud_plugin_webdav_username', '');
        $password_encrypted = $this->rc->config->get('roundcube_opencloud_plugin_webdav_password', '');
        $password = !empty($password_encrypted) ? $this->rc->decrypt($password_encrypted) : '';
        $spaces_url = $this->rc->config->get('roundcube_opencloud_plugin_webdav_spaces_url', '');

        $args['blocks']['main'] = array(
            'name'    => $this->gettext('settings_section'),
            'options' => array(),
        );

        // Info text
        $args['blocks']['main']['options']['info'] = array(
            'title'   => '',
            'content' => html::tag('p', array('class' => 'hint'), rcube::Q($this->gettext('settings_info'))),
        );

        // Username field
        $input_username = new html_inputfield(array(
            'name'  => 'roundcube_opencloud_plugin_webdav_username',
            'id'    => 'roundcube_opencloud_plugin_webdav_username',
            'size'  => 40,
            'autocomplete' => 'off',
        ));

        $args['blocks']['main']['options']['username'] = array(
            'title'   => rcube::Q($this->gettext('settings_username')),
            'content' => $input_username->show($username),
        );

        // Password field
        $input_password = new html_passwordfield(array(
            'name'  => 'roundcube_opencloud_plugin_webdav_password',
            'id'    => 'roundcube_opencloud_plugin_webdav_password',
            'size'  => 40,
            'autocomplete' => 'off',
        ));

        $args['blocks']['main']['options']['password'] = array(
            'title'   => rcube::Q($this->gettext('settings_password')),
            'content' => $input_password->show($password),
        );

        // Spaces URL field
        $input_spaces_url = new html_inputfield(array(
            'name'  => 'roundcube_opencloud_plugin_webdav_spaces_url',
            'id'    => 'roundcube_opencloud_plugin_webdav_spaces_url',
            'size'  => 60,
            'autocomplete' => 'off',
        ));

        $args['blocks']['main']['options']['spaces_url'] = array(
            'title'   => rcube::Q($this->gettext('settings_spaces_url')),
            'content' => $input_spaces_url->show($spaces_url),
        );

        return $args;
    }

    /**
     * Save Cloud Storage settings
     */
    public function preferences_save($args)
    {
        if ($args['section'] != 'roundcube_opencloud_plugin') {
            return $args;
        }

        $this->add_texts('localization/');

        $args['prefs']['roundcube_opencloud_plugin_webdav_username'] = rcube_utils::get_input_value('roundcube_opencloud_plugin_webdav_username', rcube_utils::INPUT_POST);

        $password = rcube_utils::get_input_value('roundcube_opencloud_plugin_webdav_password', rcube_utils::INPUT_POST, true);
        if (!empty($password)) {
            $args['prefs']['roundcube_opencloud_plugin_webdav_password'] = $this->rc->encrypt($password);
        } else {
            $args['prefs']['roundcube_opencloud_plugin_webdav_password'] = '';
        }

        $args['prefs']['roundcube_opencloud_plugin_webdav_spaces_url'] = rcube_utils::get_input_value('roundcube_opencloud_plugin_webdav_spaces_url', rcube_utils::INPUT_POST);

        return $args;
    }
}

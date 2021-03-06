<?php
/*
Plugin Name: IMPress for IDX Broker
Plugin URI: http://www.idxbroker.com
Description: Over 600 IDX/MLS feeds serviced. The #1 IDX/MLS solution just got even better!
Version: 2.0.1
Author: IDX Broker
Contributors: IDX, LLC
Author URI: http://www.idxbroker.com/
License: GPLv2 or later
 */

// Report all errors during development. Remember to hash out when sending to production.

// error_reporting(E_ALL);

new Idx_Broker_Plugin();
class Idx_Broker_Plugin
{
    //placed here for convenient updating
    const IDX_WP_PLUGIN_VERSION = '2.0.1';

    public function __construct()
    {

        if ($this->php_version_check()) {
            require_once 'idx' . DIRECTORY_SEPARATOR . 'autoloader.php';
            //eval is used to prevent namespace errors in parsing with PHP < 5.3
            eval('new IDX\Initiate_Plugin();');
            /** Function that is executed when plugin is activated. **/
            register_activation_hook(__FILE__, array($this, 'idx_activate'));
            register_deactivation_hook(__FILE__, array($this, 'idx_deactivate'));
            register_uninstall_hook(__FILE__, array('idx-broker-platinum', 'idx_uninstall'));
        }
    }

    /*
     * Check for versions less than PHP5.3 and display error
     */
    public function php_version_check()
    {
        if (phpversion() < 5.3) {
            add_action('admin_init', array($this, 'idx_deactivate_plugin'));
            add_action('admin_notices', array($this, 'incompatible_message'));
            return false;
        } else {
            return true;
        }
    }

    public static function incompatible_message()
    {
        echo "<div class=\"error\"><br><div>You are using a deprecated version
            of PHP. This is incompatable with the IDX Broker plugin.
            For security reasons, please contact your host and upgrade to the
            latest stable version of PHP they offer. We recommend a minimum
            of PHP 5.5.<br>For more information on what versions of PHP are
            supported with security updates, see <a
            href=\"http://support.idxbroker.com/customer/en/portal/articles/1917460-wordpress-plugin\">
            this knowledgebase article</a> and PHP.net's
            <a href=\"http://php.net/supported-versions.php\"
            target=\"_blank\">supported versions page.</a>
            </div><br></div>";
    }

    public static function idx_deactivate_plugin()
    {
        deactivate_plugins(plugin_basename(__FILE__));
    }

    public static function idx_activate()
    {
        if (!get_option('idx_results_url')) {
            add_option('idx_results_url');
        }
        update_option('idx_plugin_version', self::IDX_WP_PLUGIN_VERSION);

        //avoid 404 errors on custom posts such as wrappers by registering them then refreshing the permalink rules
        eval('$wrappers = new \IDX\Wrappers();');
        $wrappers->register_wrapper_post_type();

        flush_rewrite_rules();
    } // end idx_activate fn

    //deactivate hook
    public static function idx_deactivate()
    {
        //disable scheduled update for omnibar
        wp_clear_scheduled_hook('idx_omnibar_get_locations');
    }

    public static function idx_uninstall()
    {
        $page_id = get_option('idx_broker_dynamic_wrapper_page_id');
        if ($page_id) {
            wp_delete_post($page_id, true);
            wp_trash_post($page_id);
        }
        //clear transients made by the plugin
        eval('$idx_api = \IDX\Idx_Api;');
        eval('$idx_pages = new \IDX\Idx_Pages();');
        $idx_api->idx_clean_transients();
        $idx_pages->delete_all_idx_pages();
    }
}

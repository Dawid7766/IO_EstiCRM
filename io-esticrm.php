<?php
/**
 * Plugin Name: IO EstiCRM
 * Plugin URI: #
 * Description: Integration for EstiCRM.
 * Version: 1.0.0
 * Author: IMPLICTO
 * Author URI: https://implicto.com
 * License: GPL
 * Text Domain: io-esticrm
 * Domain Path: /languages
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!defined('IO_ESTICRM')) {
    define('IO_ESTICRM_DIR', plugin_dir_path(__FILE__));
    define('IO_ESTICRM_URL', plugin_dir_url(__FILE__));
}

include IO_ESTICRM_DIR . 'includes/io-esticrm-admin.php';
include IO_ESTICRM_DIR . 'includes/io-esticrm-integration.php';
include IO_ESTICRM_DIR . 'includes/io-esticrm-schedule.php';
include IO_ESTICRM_DIR . 'includes/io-esticrm-importer.php';
include IO_ESTICRM_DIR . 'includes/io-esticrm-logs.php';
include IO_ESTICRM_DIR . 'includes/io-esticrm-cleaner.php';
include IO_ESTICRM_DIR . 'includes/list-tables/io-esticrm-list-table-schedule.php';
include IO_ESTICRM_DIR . 'includes/list-tables/io-esticrm-list-table-logs.php';

class IoEsticrm
{
    private $license;

    public function __construct()
    {
        $this->license = get_option('ex-license');
        $this->loader();
    }

    public function loader()
    {
        new IoEsticrm_Admin();
    }
}

$plugin = new IoEsticrm();


// CRON ACTIONS

add_action('io_esticrm_cron_integration', 'io_esticrm_run_integration');
function io_esticrm_run_integration()
{
    new IoEsticrm_Integration();
}

add_action('io_esticrm_cron_importer', 'io_esticrm_run_importer');
function io_esticrm_run_importer()
{
    new IoEsticrm_Importer();
}

add_action('io_esticrm_cron_cleaner', 'io_esticrm_run_cleaner');
function io_esticrm_run_cleaner()
{
    new IoEsticrm_Cleaner();
}


// INSTALL

register_activation_hook(__FILE__, 'io_esticrm_create_database');
function io_esticrm_create_database()
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta('
            CREATE TABLE ' . $wpdb->prefix . 'io_esticrm_logs (
              id int(11) unsigned NOT NULL AUTO_INCREMENT,
              message text COLLATE utf8_polish_ci NOT NULL,
              status varchar(64) COLLATE utf8_polish_ci NOT NULL DEFAULT "",
              created_at datetime DEFAULT NULL,
              updated_at datetime DEFAULT NULL,
              PRIMARY KEY (id)
            )' . $wpdb->get_charset_collate()
    );

    dbDelta('
            CREATE TABLE ' . $wpdb->prefix . 'io_esticrm_schedule (
              id int(11) unsigned NOT NULL AUTO_INCREMENT,
              offer_id int(11) unsigned NOT NULL,
              name varchar(255) COLLATE utf8_polish_ci NOT NULL,
              status tinyint(1) DEFAULT "0",
              created_at datetime DEFAULT NULL,
              updated_at datetime DEFAULT NULL,
              PRIMARY KEY (id),
              UNIQUE KEY offer_id (offer_id, name)
            )' . $wpdb->get_charset_collate()
    );
}
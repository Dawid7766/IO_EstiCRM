<?php

if (!defined("ABSPATH")) {
    exit;
}

class IoEsticrm_Logs
{
    private $databaseTable = 'io_esticrm_logs';

    private static $instance;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public function addLog($status, $message)
    {
        global $wpdb;
        return $wpdb->query('INSERT INTO ' . $wpdb->prefix . $this->databaseTable . ' (message, status, created_at, updated_at) VALUES ("' . $message . '", "' . $status . '", NOW(), NOW())');
    }

    public function deleteLog($id)
    {
        global $wpdb;
        return $wpdb->query('DELETE FROM ' . $wpdb->prefix . $this->databaseTable . ' WHERE id = ' . (int)$id);
    }

    public function paginatie($paged = 1)
    {
        global $wpdb;
        return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . $this->databaseTable . ' ORDER BY id DESC LIMIT 25 OFFSET ' . ($paged - 1) * 25, 'ARRAY_A');
    }

    public function count()
    {
        global $wpdb;
        return $wpdb->get_var('SELECT count(*) FROM ' . $wpdb->prefix . $this->databaseTable);
    }
}
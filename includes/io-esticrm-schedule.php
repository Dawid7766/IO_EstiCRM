<?php

if (!defined("ABSPATH")) {
    exit;
}

class IoEsticrm_Schedule
{
    private $databaseTable = 'io_esticrm_schedule';

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

    public function addTask($offer_id, $name)
    {
        global $wpdb;
        return $wpdb->query('INSERT INTO ' . $wpdb->prefix . $this->databaseTable . ' (offer_id, name, created_at, updated_at) VALUES (' . (int)$offer_id . ', "' . $name . '", NOW(), NOW())');
    }

    public function deleteTask($offer_id, $name)
    {
        global $wpdb;
        return $wpdb->query('DELETE FROM ' . $wpdb->prefix . $this->databaseTable . ' WHERE offer_id = ' . (int)$offer_id . ' AND name = "' . $name . '"');
    }

    public function findTask($offer_id, $name)
    {
        global $wpdb;
        return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . $this->databaseTable . ' WHERE offer_id = ' . (int)$offer_id . ' AND name = "' . $name . '"');
    }

    public function getFirst()
    {
        global $wpdb;
        return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . $this->databaseTable . ' ORDER BY id ASC LIMIT 1');
    }

    public function paginatie($paged = 1)
    {
        global $wpdb;
        return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . $this->databaseTable . ' ORDER BY id ASC LIMIT 25 OFFSET ' . ($paged - 1) * 25, 'ARRAY_A');
    }

    public function count()
    {
        global $wpdb;
        return $wpdb->get_var('SELECT count(*) FROM ' . $wpdb->prefix . $this->databaseTable);
    }

    public function hasTasksByName($name)
    {
        global $wpdb;
        return $wpdb->get_var('SELECT count(*) FROM ' . $wpdb->prefix . $this->databaseTable . ' WHERE name = "' . (string)$name . '"');
    }
}

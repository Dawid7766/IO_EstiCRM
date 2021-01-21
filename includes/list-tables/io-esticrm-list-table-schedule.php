<?php

if (!defined("ABSPATH")) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class IoEsticrmListTableSchedule extends WP_List_Table
{

    public function __construct()
    {
        parent::__construct([
            'singular' => __('Harmonogram', 'io-esticrm'),
            'plural' => __('Harmonogram', 'io-esticrm'),
            'ajax' => false
        ]);
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
            case 'offer_id':
            case 'created_at':
                return $item[$column_name];
            default:
                return '';
        }
    }

    function get_columns()
    {
        $columns = [
            'offer_id' => __('ID Oferty', 'io-esticrm'),
            'name' => __('Integracja', 'io-esticrm'),
            'created_at' => __('Data', 'io-esticrm'),
        ];

        return $columns;
    }


    public function prepare_items()
    {
        $schedule = IoEsticrm_Schedule::get_instance();

        $columns = $this->get_columns();

        $this->_column_headers = array($columns, array(), array());

        $current_page = $this->get_pagenum();
        $total_items = $schedule->count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => 25
        ]);

        $this->items = $schedule->paginatie($current_page);
    }

    public function no_items()
    {
        _e('Brak zadań', 'io-esticrm');
    }
}
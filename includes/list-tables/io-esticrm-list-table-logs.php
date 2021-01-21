<?php

if (!defined("ABSPATH")) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class IoEsticrmListTableLogs extends WP_List_Table
{

    public function __construct()
    {
        parent::__construct([
            'singular' => __('Logi', 'io-esticrm'),
            'plural' => __('Logi', 'io-esticrm'),
            'ajax' => false
        ]);
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'message':
            case 'created_at':
                return $item[$column_name];
            case 'status':
                if ($item[$column_name] == 'success') {
                    return '<span style="color:green;">' . __('Sukces', 'io-esticrm') . '</span>';
                } elseif ($item[$column_name] == 'error') {
                    return '<span style="color:red;">' . __('Błąd', 'io-esticrm') . '</span>';
                } elseif ($item[$column_name] == 'warning') {
                    return '<span style="color:orange;">' . __('Ostrzeżenie', 'io-esticrm') . '</span>';
                } else {
                    return '<span style="color:gray;">' . $item[$column_name] . '<span>';
                }
            default:
                return '';
        }
    }

    function get_columns()
    {
        $columns = [
            'message' => __('Treść', 'io-esticrm'),
            'status' => __('Status', 'io-esticrm'),
            'created_at' => __('Data', 'io-esticrm'),
        ];

        return $columns;
    }


    public function prepare_items()
    {
        $logs = IoEsticrm_Logs::get_instance();

        $columns = $this->get_columns();

        $this->_column_headers = array($columns, array(), array());

        $current_page = $this->get_pagenum();
        $total_items = $logs->count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => 25
        ]);

        $this->items = $logs->paginatie($current_page);
    }

    public function no_items()
    {
        _e('Brak logów', 'io-esticrm');
    }
}
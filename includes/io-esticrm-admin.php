<?php

if (!defined("ABSPATH")) {
    exit;
}

class IoEsticrm_Admin
{
    public $list;

    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'io_esticrm_admin_page']);
        }
    }

    public function io_esticrm_admin_page()
    {
        add_menu_page(__('IO EstiCRM', 'io-esticrm'), __('IO EstiCRM', 'io-esticrm'), 'manage_options', 'io-esticrm', array($this, 'io_esticrm_manager'), 'dashicons-backup', 30);
    }

    public function io_esticrm_manager()
    {
        echo '<div class="wrap">';
        echo '<h2>' . __('IO EstiCRM', 'io-esticrm') . '</h2>';

        if ($_GET['logs']) {
            echo '<h3>' . __('Logi', 'io-esticrm') . '</h3>';
            $this->list = new IoEsticrmListTableLogs();
        } else {
            echo '<h3>' . __('Harmonogram', 'io-esticrm') . '</h3>';
            $this->list = new IoEsticrmListTableSchedule();
        }

        $this->list->prepare_items();
        $this->list->display();

        echo '<a href="' . admin_url('admin.php?page=io-esticrm') . '" class="button" style="margin-right: 15px;">' . __('Harmonogram', 'io-esticrm') . '</a>';
        echo '<a href="' . admin_url('admin.php?page=io-esticrm&logs=1') . '" class="button">' . __('Logi', 'io-esticrm') . '</a>';
        echo '</div>';
    }
}
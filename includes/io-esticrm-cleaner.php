<?php

if (!defined("ABSPATH")) {
    exit;
}

class IoEsticrm_Cleaner
{
    private $fileDir = WP_CONTENT_DIR . '/esticrm/';

    private $logs;

    public function __construct()
    {
        $this->logs = IoEsticrm_Logs::get_instance();

        try {
            $this->runCleaner();
            $this->logs->addLog('success', 'Czyszczenie folderów zakończono pomyślnie');
        } catch (Exception $e) {
            $this->logs->addLog('error', 'Czyszczenie folderów zakończono błędem');
        }
    }

    private function runCleaner()
    {
        $files = glob($this->fileDir . 'EstiCRM*');
        foreach ($files as $file) {
            if (time() - filemtime($file) >= 60 * 60 * 24) {
                if (IoEsticrm_Schedule::get_instance()->hasTasksByName(basename($file)) == false) {
                    if (is_dir($file))
                        $this->deleteDirectory($file);
                    else
                        unlink($file);
                }
            }
        }
    }

    private function deleteDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") $this->deleteDirectory($dir . "/" . $object); else unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
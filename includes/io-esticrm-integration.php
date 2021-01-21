<?php

if (!defined("ABSPATH")) {
    exit;
}

class IoEsticrm_Integration
{
    private $fileDir = WP_CONTENT_DIR . '/esticrm/';
    private $logs;

    public function __construct()
    {
        $this->logs = IoEsticrm_Logs::get_instance();

        try {
            $this->runSchedule();
            $this->logs->addLog('success', 'Pobranie plików zakończone pomyślnie');
        } catch (Exception $e) {
            $this->logs->addLog('error', 'Pobieranie plików nie udało się');
        }
    }

    public function runSchedule()
    {
        $files = $this->getFiles();

        foreach ($files as $file) {
            if ($this->unzipFile(basename($file))) {
                $this->schedule(basename($file, '.zip'));
                $this->removeZipFile(basename($file));
            }
        }
    }

    private function getFiles()
    {
        $files = glob($this->fileDir . '*.zip');
        if ($files) {
            usort($files, function ($x, $y) {
                return filemtime($x) > filemtime($y);
            });
        }

        return $files;
    }

    private function unzipFile($fileName)
    {
        $destination = basename($fileName, '.zip');
        $zipArchive = new ZipArchive();
        $result = $zipArchive->open($this->fileDir . $fileName);
        if ($result === TRUE) {
            $zipArchive->extractTo($this->fileDir . $destination);
            $zipArchive->close();
            return true;
        }

        return false;
    }

    private function schedule($name)
    {
        $schedule = IoEsticrm_Schedule::get_instance();

        $files = glob($this->fileDir . $name . '/EstiCRM*.xml');
        foreach ($files as $file) {
            if (file_exists($file)) {
                $properties = simplexml_load_file($file);
                foreach ($properties as $property) {
                    $schedule->addTask((int)$property->id, $name);
                }
            }
        }
    }

    private function removeZipFile($fileName)
    {
        unlink($this->fileDir . $fileName);
    }
}
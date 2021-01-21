<?php

if (!defined("ABSPATH")) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class IoEsticrm_Importer
{
    private $keyName = 'ESTICRM_ID';

    private $fileDir = WP_CONTENT_DIR . '/esticrm/';
    private $fileUrl = WP_CONTENT_URL . '/esticrm/';

    private $definitions;
    private $property;
    private $post;

    private $authorID = 1;

    private $offerID;
    private $integrationName;

    private $logs;

    public function __construct()
    {
        $this->logs = IoEsticrm_Logs::get_instance();

        if ($this->loadImportTask()) {
            try {
                $this->runImport();
                $this->logs->addLog('success', 'Import: ' . $this->offerID . ' - ' . $this->integrationName . ' zakończono pomyślnie');
            } catch (Exception $e) {
                $this->logs->addLog('error', 'Import oferty: ' . $this->offerID . ' - ' . $this->integrationName . ' zakończony błędem');
            }
        }
    }

    private function loadImportTask()
    {
        $schedule = IoEsticrm_Schedule::get_instance();
        $task = $schedule->getFirst();
        if ($task) {
            $this->offerID = $task->offer_id;
            $this->integrationName = $task->name;
            return true;
        }
        return false;
    }

    private function runImport()
    {
        if ($this->loadDefinitions() && $this->loadProperty()) {
            $this->importController();
            IoEsticrm_Schedule::get_instance()->deleteTask($this->offerID, $this->integrationName);
        }
    }

    private function loadDefinitions()
    {
        $definitionsPath = $this->fileDir . $this->integrationName . '/definitions.xml';
        if (file_exists($definitionsPath)) {
            $this->definitions = simplexml_load_file($definitionsPath);
            if ($this->definitions) {
                return true;
            }
        }

        return false;
    }

    private function loadProperty()
    {
        $files = glob($this->fileDir . $this->integrationName . '/EstiCRM*.xml');
        if (!empty($files) && file_exists($files[0])) {
            $properties = simplexml_load_file($files[0]);
            foreach ($properties as $property) {
                if ((int)$property->id == $this->offerID) {
                    $this->property = $property;
                    return true;
                }
            }
        }

        return false;
    }

    private function importController()
    {
        if ($this->property->action == 'create' || $this->property->action == 'update') {
            $this->importPost();
            if ($this->post) {
                $this->importPostMeta();
                $this->importAgent();
                $this->importMedia();
                $this->importTaxonomies();
            } else {
                throw new Exception('Read or create post failed');
            }

        } elseif ($this->property->action == 'delete') {
            $this->deletePost();
        }
    }

    private function importPost()
    {
        $this->findPost();
        if ($this->post) {
            $this->updatePost();
        } else {
            $this->createPost();
        }
    }

    private function findPost()
    {
        global $wpdb;
        $postID = $wpdb->get_var('SELECT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "' . $this->keyName . '" AND meta_value = ' . (int)$this->property->id);
        if ($postID) {
            $this->post = get_post($postID);
        }
    }

    private function createPost()
    {
        $postID = wp_insert_post(array(
            'post_title' => (!empty($this->property->portalTitle) ? $this->property->portalTitle : 'Nieruchomość ' . $this->property->id),
            'post_content' => $this->property->descriptionWebsite,
            'post_status' => 'publish',
            'post_type' => 'property',
            'post_author' => $this->authorID,
        ));

        if ($postID) {
            update_post_meta($postID, $this->keyName, (int)$this->property->id);
            $this->post = get_post($postID);
            return true;
        }

        return false;
    }

    private function updatePost()
    {
        wp_update_post(array(
            'ID' => $this->post->ID,
            'post_title' => (!empty($this->property->portalTitle) ? $this->property->portalTitle : 'Nieruchomość ' . $this->property->id),
            'post_content' => $this->property->descriptionWebsite,
            'post_status' => 'publish'
        ));
    }

    private function deletePost()
    {
        global $wpdb;
        $postID = $wpdb->get_var('SELECT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "' . $this->keyName . '" AND meta_value = ' . (int)$this->property->id);
        if ($postID) {
            wp_delete_post($postID);
            return true;
        }
        return false;
    }

    private function importPostMeta()
    {
        $postID = $this->post->ID;
        update_post_meta($postID, 'REAL_HOMES_property_id', (string)$this->property->number);
        update_post_meta($postID, 'REAL_HOMES_property_price', (string)$this->property->price);

        if ((string)$this->property->pricePermeter) {
            update_post_meta($postID, 'REAL_HOMES_property_price_postfix', '/ ' . (string)$this->property->pricePermeter . '/m²');
        }

        update_post_meta($postID, 'REAL_HOMES_property_size', (string)$this->property->areaTotal);
        update_post_meta($postID, 'REAL_HOMES_property_size_postfix', 'm²');
        update_post_meta($postID, 'REAL_HOMES_property_year_built', (string)$this->property->buildingYear);
        update_post_meta($postID, 'REAL_HOMES_property_bedrooms', (string)$this->property->apartmentBedroomNumber);
        update_post_meta($postID, 'REAL_HOMES_property_bathrooms', (string)$this->property->apartmentBathroomNumber);

        $address = sprintf(
            '%s %s, %s, %s, %s, %s',
            (string)$this->property->locationStreetType,
            (string)$this->property->locationStreetName,
            (string)$this->property->locationPrecinctName,
            (string)$this->property->locationCityName,
            (string)$this->property->locationProvinceName,
            (string)$this->property->locationCountryName
        );
        update_post_meta($postID, 'REAL_HOMES_property_address', $address);

        $location = sprintf('%s,%s,%s', (string)$this->property->locationLatitude, (string)$this->property->locationLongitude, 0);
        update_post_meta($postID, 'REAL_HOMES_property_location', $location);
    }

    private function importAgent()
    {
        if ($this->property->contactFirstname && $this->property->contactLastname) {
            $person = (string)sprintf('%s %s', trim((string)$this->property->contactFirstname), trim((string)$this->property->contactLastname));
            $agent = get_page_by_title($person, OBJECT, 'agent');
            if ($agent) {
                $agentID = $agent->ID;
            } else {
                $agentID = wp_insert_post(array(
                    'post_title' => $person,
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_type' => 'agent',
                    'post_author' => $this->authorID,
                ));

                update_post_meta($agentID, 'REAL_HOMES_agent_email', (string)$this->property->contactEmail);
                update_post_meta($agentID, 'REAL_HOMES_mobile_number', (string)$this->property->contactPhone);
            }

            update_post_meta($this->post->ID, 'REAL_HOMES_agents', $agentID);
        }
    }

    private function importMedia()
    {
        global $wpdb;

        $hasPostThumbnail = has_post_thumbnail($this->post);

        $pictures = $this->property->pictures;
        if (!empty($pictures)) {
            foreach ($pictures->picture as $picture) {
                $target = $this->fileUrl . $this->integrationName . '/' . (string)$picture;
                $fileName = pathinfo((string)$picture, PATHINFO_FILENAME);
                $attachmentID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='attachment'", $fileName));
                if (!$attachmentID) {
                    $attachmentID = media_sideload_image($target, $this->post->ID, null, 'id');
                    add_post_meta($this->post->ID, 'REAL_HOMES_property_images', $attachmentID);
                }

                if (!$hasPostThumbnail) {
                    set_post_thumbnail($this->post, $attachmentID);
                    $hasPostThumbnail = true;
                }
            }
        }
    }

    private function importTaxonomies()
    {
        // property-feature
        $propertyFeatures = array();
        $features = $this->mapFeatures();
        foreach ($features as $key => $feature) {
            if ((string)$this->property->$key) {
                $propertyFeatures[] = $feature;
            }
        }
        wp_set_object_terms($this->post->ID, $propertyFeatures, 'property-feature', false);

        // property-city
        if ((string)$this->property->locationCityName) {
            wp_set_object_terms($this->post->ID, (string)$this->property->locationCityName, 'property-city', false);
        }

        // property-status
        if ((string)$this->property->transaction) {
            $transaction = $this->getDefinition((string)$this->property->transaction->attributes()->dictionary, (string)$this->property->transaction);
            if ($transaction) {
                wp_set_object_terms($this->post->ID, (string)$transaction, 'property-status', false);
            }
        }

        // property-type
        if ((string)$this->property->mainTypeId) {
            $type = $this->getDefinition((string)$this->property->mainTypeId->attributes()->dictionary, (string)$this->property->mainTypeId);
            if ($type) {
                wp_set_object_terms($this->post->ID, (string)$type, 'property-type', false);
            }
        }
    }

    private function mapFeatures()
    {
        return array(
            'additionalParkingunderground' => 'Parking podziemny',
            'additionalParking' => 'Parking naziemny',
            'additionalGarden' => 'Ogród',
            'buildingSwimmingpool' => 'Basen',
            'buildingElevatorhoist' => 'Winda towarowa',
            'additionalBalcony' => 'Balkon',
            'additionalLoggia' => 'Balkon typu "Loggia"',
            'mediaInternet' => 'Internet',
            'mediaTelevision' => 'Telewizor',
            'additionalBasement' => 'Piwnica',
            'buildingElevatornumber' => 'Winda',
            'securitySecuredoor' => 'Drzwi Antywłamaniowe',
            'additionalTerrace' => 'Taras'
        );
    }

    private function getDefinition($label, $value)
    {
        foreach ($this->definitions->$label->children() as $transaction) {
            if ($transaction['key'] == $value) {
                return (string)$transaction;
            }
        }

        return false;
    }
}
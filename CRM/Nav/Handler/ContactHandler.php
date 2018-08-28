<?php
/*-------------------------------------------------------+
| Navision API Tools                                     |
| Heinrich Böll Stiftung                                 |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

class CRM_Nav_Handler_ContactHandler extends CRM_NAV_Handler_HandlerBase {

  public function __construct() {
  }

  public function process() {
    if (!$this->check_record_type()) {
      return;
    }
    $contact_id = $this->get_contact_id_from_nav_id($this->record);
    if ($this->check_delete_record()) {
      $this->delete_nav_id_from_contact($contact_id);
      $this->record->set_consumed();
      $this->log("Deleted nav_id from contact");
      return;
    }

    $nav_id = $this->record->get_navision_id();
    $contact_id = $this->get_contact_id_from_nav_id($nav_id);
    // get or create with XCM
    $contact_id = $this->check_or_create_contact_id($contact_id);
    // add NavId to Contact
    $this->add_nav_id_to_contact($contact_id, $nav_id);

    $changed_entities = $this->get_update_values();

    if (!$this->check_nav_before_vs_civi($changed_entities, $contact_id)) {
      // TODO: i3Val command here now for all Data!
    }
    // TODO: Add values to civiCRM
    // set consumed
  }

  private function check_nav_before_vs_civi($entities, $contact_id) {
    try {
      $this->check_civi_contact_data($entities['Contact'], $contact_id);
      $this->check_civi_address_data($entities['Address'], $contact_id);
    } catch (Exception $e) {
      $this->log("Navision Data (before) doesn't match Civi Data. Proceeding with i3Val. Message: {$e->getMessage()}");
      return FALSE;
    }
  }

  private function check_civi_address_data($data, $contact_id) {
    // TODO: implement
  }

  private function check_civi_contact_data($data, $contact_id) {
    // nothing to do here
    if (!isset($data)) {
      return;
    }
    // get Contact
    foreach ($data as $contact_data) {
      if ($contact_data['contact_type'] == 'Individual') {
        $result = civicrm_api3('Contact', 'get', array(
          'sequential' => 1,
          'id' => $contact_id,
        ));
        if ($result['is_error'] == '1') {
          throw new Exception("Couldn't find Contact with ID {$contact_id}");
        }
        $this->compare_data($contact_data, $result['values']);
      }
    }
  }

  /**
   * @param $nav_data
   * @param $civi_query_result
   *
   * @throws \Exception
   */
  private function compare_data($nav_data, $civi_query_result) {
    foreach ($nav_data as $nav_civi_key => $nav_value) {
      if ($civi_query_result[$nav_civi_key] != $nav_value) {
        throw new Exception("Value Mismatch - Nav Data: '$civi_query_result[$nav_civi_key]' != '{$nav_value}' (CiviData)");
      }
    }
  }

  // FixMe: Obsolete
  private function get_civi_entity($entity, $contact_id) {
    // add additional custom return fields
    if ($entity == "Contact") {
      $values['return'] = ["custom_41"];
      $values['id']     = $contact_id;
    } else {
      $values['contact_id'] = $contact_id;
    }
    $result = civicrm_api3($entity, 'get', $values);
    if ($result['is_error'] == 1) {
      return "";
    }
    return $result['values'];
  }

  private function add_nav_id_to_contact($contact_id, $nav_id) {
    $values = array(
      'id'          => $contact_id,
      'custom_147'  =>  $nav_id,
    );
    $result = civicrm_api3('Contact', 'create', $values);
    if ($result['is_error'] == 1) {
      $this->log("Couldn't add Navision ID to contact ({$contact_id}).");
      throw new Exception("Couldn't add Navision ID to contact ({$contact_id}).");
    }
  }

  private function check_or_create_contact_id($contact_id) {
    // if contact_id is empty, get/create contact via XCM with first_name, last_name, email
    if ($contact_id == "") {
      $values = $this->record->get_xcm_contact_details();
      // get/create via XCM
      $result = civicrm_api3('Contact', 'getorcreate', $values);
      return $result['id'];
    }
    return $contact_id;
  }

  private function check_delete_record() {
    return $this->record->get_change_type() == 'Delete';
  }

  private function delete_nav_id_from_contact($contact_id) {
    $result = civicrm_api3('Contact', 'create', array(
      'sequential' => 1,
      'contact_type' => "Individual",
      'id' => $contact_id,
      'custom_147' => "",
    ));
    if ($result['is_error'] != '1') {
      throw new Exception("Error occured while removing NavisionId from Contact {$contact_id}. Error Message: {$result['error_message']}");
    }
  }

  /**
   * @param $type (before|after)
   *
   * @return array
   */
  private function get_update_values($type) {
    $changed_civi_entities   = [];
    $changed_civi_entities[] = $this->record->get_changed_contact_values($type);
    $changed_civi_entities[] = $this->record->get_changed_address_values($type);
    $changed_civi_entities[] = $this->record->get_changed_phone_values($type);
    $changed_civi_entities[] = $this->record->get_changed_mail_values($type);
    $changed_civi_entities[] = $this->record->get_changed_website_values($type);

    return $changed_civi_entities;
  }

  /**
   * Check if the record is a civiContRelation
   * @return bool
   */
  protected function check_record_type() {
    return $this->record->get_type() == 'civiContact';
  }
}
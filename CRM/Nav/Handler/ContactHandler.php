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

    // FIXME: THIS ISNT SUPPOSED TO HAPPEN RIGHT NOW!
//    $this->add_entities_to_civicrm($changed_entities);

    // Check ob die alten Werte so schon in CiviCRM vorhanden sind
  }

  // TODO: This might not work. check if entity exists first here?
  // FixME
  private function add_entities_to_civicrm($changed_entities) {
    foreach ($changed_entities as $entity => $values) {
      $result = civicrm_api3($entity, 'create', $values);
      if ($result['is_error'] == '1') {
        $this->log("API Error occured: {$result['error_message']}");
      }
    }
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

  private function get_update_values() {
    $changed_civi_entities = array();
    $changed_civi_entities[] = $this->record->get_changed_contact_values();
    $changed_civi_entities[] = $this->record->get_changed_address_values();
    $changed_civi_entities[] = $this->record->get_changed_phone_values();
    $changed_civi_entities[] = $this->record->get_changed_mail_values();
    $changed_civi_entities[] = $this->record->get_changed_website_values();

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
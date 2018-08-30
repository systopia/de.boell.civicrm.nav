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

class CRM_Nav_Handler_ContactHandler extends CRM_Nav_Handler_HandlerBase {

  public function __construct($record) {
    parent::__construct($record);
  }

  public function process() {
    if (!$this->check_record_type()) {
      return;
    }
    $nav_id = $this->record->get_individual_navision_id();
    $contact_id = $this->get_contact_id_from_nav_id($nav_id);
    if ($this->check_delete_record()) {
      $this->delete_nav_id_from_contact($contact_id);
      $this->record->set_consumed();
      $this->log("Deleted nav_id from contact");
      return;
    }

    $contact_id = $this->get_or_create_contact($contact_id);
    if ($contact_id < '0') {
      // TODO: Contact is created with all values (AFTER).
      // nothing to do here anymore.
      // TODO: mark record as consumed
      $this->record->set_consumed();
      return;
    }
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
      $this->navision_custom_field  =>  $nav_id,
    );
    $result = civicrm_api3('Contact', 'create', $values);
    if ($result['is_error'] == 1) {
      $this->log("Couldn't add Navision ID to contact ({$contact_id}).");
      throw new Exception("Couldn't add Navision ID to contact ({$contact_id}).");
    }
  }

  /**
   * If contact coulddn't be identified by NavId, it will be identified by email(s)
   * and first_name and last name. If that's not available, a new contact is created
   * with the AFTER values and ALL provided values
   * @param $contact_id
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function get_or_create_contact($contact_id) {
    // if contact_id is empty, get/create contact via XCM with first_name, last_name, email
    if ($contact_id == "") {
      $contact_lookup_details = $this->record->get_contact_lookup_details();
      $email_contact_ids = $this->get_contact_ids_via_emails($contact_lookup_details['Emails']);
      $lookup_contact_id = $this->get_contact($email_contact_ids, $contact_lookup_details['Contact']);

      if ($lookup_contact_id == "") {
        // create contact with all available data, then return '-1' to abort further processing
        try {
          $this->create_civi_full_contact();
        } catch (Exception $e) {
          $this->log($e->getMessage());
          return '-2';
        }
        return '-1';
      }
      return $lookup_contact_id;
    }
    return $contact_id;
  }

  /**
   * @throws \Exception
   */
  private function create_civi_full_contact() {
    // create Contact
    $contact_data = $this->record->get_contact_details('Individual');
    $contact_id = $this->create_civi_entity($contact_data, 'Contact');

    foreach ($this->record->get_civi_addresses() as $address) {
      $address['contact_id'] = $contact_id;
      $this->create_civi_entity($address, 'Address');
    }
    foreach ($this->record->get_civi_phones() as $phone) {
      $phone['contact_id'] = $contact_id;
      $this->create_civi_entity($phone, 'Phone');
    }
    foreach ($this->record->get_civi_emails() as $email) {
      $email['contact_id'] = $contact_id;
      $this->create_civi_entity($email, 'Email');
    }
    foreach ($this->record->get_civi_website() as $website) {
      $website['contact_id'] = $contact_id;
      $this->create_civi_entity($website, 'Website');
    }
  }

  private function create_civi_entity($values, $entity) {
    $result = civicrm_api3($entity, 'create', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("Couldn't create Civi Entity {$entity}. Error Message: " . $result['error_message']);
    }
    return $result['id'];
  }

  /**
   * @param $contact_ids      (array with contact_ids from email lookup)
   * @param $contact_details  (array first_name, last_name)
   *
   * @return string (contact_id or "")
   */
  private function get_contact($contact_ids, $contact_details) {
    if (!empty($contact_ids)) {
      $contact_details['id'] = array('IN' => $contact_ids);
    }
    $result = civicrm_api3('Contact', 'get', $contact_details);
    if ($result['is_error'] == '1') {
      $this->log("Error occured while looking up contacts. Message: " . $result['error_message']);
      return "";
    }
    if ($result['count'] != '1') {
      $this->log("Found {$result['count']} entries for contact.");
      return "";
    }
    return $result['values']['contact_id'];
  }

  private function get_contact_ids_via_emails($emails) {
    if (empty($emails)) {
      return array();
    }
    $result = civicrm_api3('Email', 'get', array(
      'sequential' => 1,
      'email' => array('IN' => $emails),
    ));
    if ($result['is_error'] == '1') {
      $this->log("Api command to get Emails Entities failed. Reason: {$result['error_message']}");
      return array();
    }
    $contact_ids = array();
    foreach ($result['values'] as $val) {
      $contact_ids[] = $val['contact_id'];
    }
    return $contact_ids;
  }

  private function check_delete_record() {
    return $this->record->get_change_type() == 'Delete';
  }

  private function delete_nav_id_from_contact($contact_id) {
    $result = civicrm_api3('Contact', 'create', array(
      'sequential' => 1,
      'contact_type' => "Individual",
      'id' => $contact_id,
      $this->navision_custom_field => "",
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
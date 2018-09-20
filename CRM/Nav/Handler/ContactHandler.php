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

  private $i3Val_values = array();

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

    if ($this->check_new_record()) {
      try {
        $this->create_civi_full_contact();
      } catch (Exception $e) {
        $this->log($e->getMessage());
        return;
      }
      $this->record->set_consumed();
      return;
    }

    $contact_id = $this->get_or_create_contact($contact_id);
    // contact is created, all new values are already added as well
    if ($contact_id < '0') {
      $this->record->set_consumed();
      return;
    }
    // add NavId to Contact
    $this->add_nav_id_to_contact($contact_id, $nav_id);

    $changed_entities = $this->get_update_values('before');

    // remove old linked address in case company has changed
    $this->remove_old_linked_organisation_address($contact_id);
    // TODO: Check/filter Values first
    // --> then update Contact data WITHOUT conflicting values
    // --> then put values to i3Val
    $this->check_nav_before_vs_civi($changed_entities, $contact_id);

    // valid change operation
    $this->update_values($contact_id);
    $this->fill_unchanged_values($contact_id);

    $this->update_values_with_i3val($contact_id);
    $this->record->set_consumed();
  }

  private function fill_unchanged_values($contact_id) {
    $this->fill_update_entity($contact_id, 'Phone');
    $this->fill_update_entity($contact_id, 'Address');
    $this->fill_update_entity($contact_id, 'Email');
    $this->fill_update_entity($contact_id, 'Website');
  }

  /**
   * @param $contact_id
   * @param $entity
   */
  private function fill_update_entity($contact_id, $entity) {
    $get_unchanged_value_function_name = "get_unchanged_{$entity}_values";
    $data_records = $this->record->{$get_unchanged_value_function_name}();
    foreach ($data_records[$entity]['before'] as $key => $data) {
      if (empty($data)) {
        continue;
      }
      $entity_id = $this->get_entity_id($data, $contact_id, $entity);
      if (!empty($entity_id)) {
        $data_records[$entity]['after'][$key]['id'] = $entity_id;
      }
      $data_records[$entity]['after'][$key]['contact_id'] = $contact_id;
      $id = $this->create_civi_entity($data_records[$entity]['after'][$key], $entity);
    }
    // Lookup before values
    // Found -> update with after
    // not found -> create
  }

  /**
   * @param $contact_id
   */
  private function update_values($contact_id) {
    $this->create_linked_organisation_address($contact_id);

    $contact_data = $this->record->get_changed_contact_values('after');
    $address_data =  $this->record->get_changed_Address_values('before');
    $mail_data =  $this->record->get_changed_Email_values('before');
    $phone_data =  $this->record->get_changed_Phone_values('before');
    $website_data =  $this->record->get_changed_Website_values('before');

    $this->filter_i3Val_values('Contact', $contact_data );
    $this->filter_i3Val_values('Address', $address_data );
    $this->filter_i3Val_values('Email', $mail_data );
    $this->filter_i3Val_values('Phone', $phone_data );
    $this->filter_i3Val_values('Website', $website_data );
    // update Contact
    if (!empty($contact_data)) {
      $this->set_values($contact_id, $contact_data, 'Contact');
    }
    $this->update_entity($address_data, $contact_id, 'Address');
    $this->update_entity($mail_data, $contact_id, 'Email');
    $this->update_entity($phone_data, $contact_id, 'Phone');
    $this->update_entity($website_data, $contact_id, 'Website');
  }

  private function update_entity($entity_values, $contact_id, $entity) {
    if (empty($entity_values[$entity])) {
      // FixMe: Is this correct? What happens if before value is empty, and after value is set? (e.g. fill up)
      return;
    }
    $get_changed_value_function_name = "get_changed_{$entity}_values";
    $after_data_records = $this->record->{$get_changed_value_function_name}('after');
    $entity_ids = array();
    foreach ($entity_values[$entity] as $key => $value) {
      $entity_id = $this->get_entity_id($value, $contact_id, $entity);
      if ($entity_id == "") {
        // try with after values, otherwise we always fill aditional values
        // in case an after value is already in the system
        // TOOD: is this only relevant for testing?
        $entity_id = $this->get_entity_id($after_data_records[$entity][$key], $contact_id, $entity);
      }
      $entity_ids[$key] = $entity_id;
    }
    foreach ($after_data_records[$entity] as $key => $value) {
      $this->set_values($entity_ids[$key], $value, $entity, $contact_id);
    }
  }

  /**
   * Filters Elements from changed data to be updated by the previously determined
   * i3Val values. Those will be passed to i3Val afterwards
   *
   * @param $entity
   * @param $data_record
   */
  private function filter_i3Val_values($entity, &$data_record) {
    if (!isset($this->i3Val_values[$entity])) {
      //nothing to do here. Entity doesn't need i3Val
      return;
    }
    // iterate entity values and check against i3Val arrays
    foreach($data_record[$entity] as $key => $value) {
      foreach ($this->i3Val_values[$entity] as $i3_value) {
        if ($i3_value == $value) {
          unset($data_record[$entity][$key]);
        }
      }
    }
  }

  private function set_values($entity_id, $values, $entity, $contact_id) {
    if (empty($entity_id)) {
      // add contact ID, since we add a new Entityt to a given contact_id
      $values['contact_id'] = $contact_id;
    } else {
      $values['id'] = $entity_id;
    }
    $result = civicrm_api3($entity, 'create', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("Error occured while setting values for {$entity}({$entity_id}) with values (" . json_encode($values) . "). Error Message: {$result['error_message']}");
    }
  }

  /**
   * @param $contact_id
   *
   * @throws \Exception
   */
  private function update_values_with_i3val($contact_id) {
    // indices
    $email_index = '0';
    $phone_index = '0';
    $website_index = '0';
    $address_index = array(); // TODO: array not needed, only individual address is used

    $contact_details = $this->record->get_contact_details();

    $emails = $this->record->get_i3val_values('Email', $this->i3Val_values['Email']);
    $phones = $this->record->get_i3val_values('Phone', $this->i3Val_values['Phone']);
    $websites = $this->record->get_i3val_values('Website', $this->i3Val_values['Website']);
    $addresses = $this->record->get_i3val_values('Address', $this->i3Val_values['Address']);

    $this->add_value_from_additional_entity($contact_details, $emails,$email_index,'email');
    $this->add_value_from_additional_entity($contact_details, $phones,$phone_index,'phone');
    $this->add_value_from_additional_entity($contact_details, $websites,$website_index,'url');
    $this->add_value_from_additional_entity($contact_details, $addresses,$address_index);

    $this->push_values_to_i3val($contact_details, $contact_id);

    $contact_details = array();
    while ( $this->add_value_from_additional_entity($contact_details, $emails,$email_index,'email') ||
            $this->add_value_from_additional_entity($contact_details, $phones,$phone_index,'phone') ||
            $this->add_value_from_additional_entity($contact_details, $websites,$website_index,'url') ||
            $this->add_value_from_additional_entity($contact_details, $addresses,$address_index)
    ) {
      $this->push_values_to_i3val($contact_details, $contact_id);
      $contact_details = array();
    }
  }

  /**
   * @param $contact_data (array with contact details, will be filled up )
   * @param $values (Email|Website|Phone)-Data array of Entities in civi
   *   format)
   * @param $index (Index of the to be used element, or array with address
   *   indices. If array, the only used for addresses)
   * @param $value_key (index for value, e.g. phone for Phone entity, url for
   *   website, email for Email)
   * @return bool
   */
  private function add_value_from_additional_entity(&$contact_data, $values, &$index, $value_key ='') {
    if (is_array($index)) {
      // we have the address index. Find index in values not in $index
      foreach ($values as $key => $address) {
        if (!in_array($key, $index)) {
          foreach ($address as $address_key => $address_value) {
            $contact_data[$address_key] = $address_value;
          }
          $index[] = $key;
          return TRUE;
        }
      }
      // nothing to do anymore
      return FALSE;
    }
    // either phone, website, email
    if (isset($values[$index])) {
      $contact_data[$value_key] = $values[$index][$value_key];
      $index += 1;
      return TRUE;
    }
    return FALSE;
  }

  private function push_values_to_i3val($values, $contact_id) {
    $values['id'] = $contact_id;
    $values['i3val_note'] = "Automatically added by Navision synchronisation";
    error_log("i3Val Values: " . json_encode($values));
    $result = civicrm_api3('Contact', 'request_update', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("i3Val call error. Message: {$result['error_message']}");
    }
  }

  private function check_nav_before_vs_civi($entities, $contact_id) {

    foreach ($entities as $entity) {
      try {
        // TODO: check Civi Organisation data?? Currently only Individual is checked!
        $this->check_civi_contact_data($entity['Contact'], $contact_id);
        $this->check_civi_entity_data($entity['Address'], $contact_id, 'Address');
        $this->check_civi_entity_data($entity['Phone'], $contact_id, 'Phone');
        $this->check_civi_entity_data($entity['Email'], $contact_id, 'Email');
        $this->check_civi_entity_data($entity['Website'], $contact_id, 'Website');
      } catch (Exception $e) {
        // TODO: Setup ENTITY (+ KEY) for i3VAL processing
        $this->log("Navision Data (before) doesn't match Civi Data. Proceeding with i3Val. Message: {$e->getMessage()}");
        $entity_name = key($entity);
        $this->i3Val_values[$entity_name] = $entity[$entity_name];
      }
    }
  }


  private function check_civi_entity_data($navision_data, $contact_id, $entity) {
    if (!isset($navision_data)) {
      return;
    }
    foreach ($navision_data as $data) {
      if (empty($data)) {
        // empty data here, means we have to create this field to fill up contact
        // values will be filled later, for now we don't need i3Val
        return;
      }
      $result = civicrm_api3($entity, 'get', array(
        'sequential' => 1,
        'contact_id' => $contact_id,
      ));
      $this->verify_civi_data_against_navision_data($result, $data, $contact_id);
    }
  }

  /**
   * @param $data
   * @param $contact_id
   *
   * @throws \CiviCRM_API3_Exception
   */
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
        $this->verify_civi_data_against_navision_data($result, $contact_data, $contact_id);
      }
    }
  }

  /**
   * @param $civi_result_values
   * @param $navision_data
   * @param $contact_id
   *
   * @throws \Exception
   */
  private function verify_civi_data_against_navision_data($civi_result_values, $navision_data, $contact_id) {
    if ($civi_result_values['is_error'] == '1') {
      throw new Exception("API Error, couldn't find any data to Contact '{$contact_id}'");
    }
    foreach ($civi_result_values['values'] as $civi_result) {
      if ($this->compare_data($navision_data, $civi_result)) {
        return;
      }
    }
    // TODO: add to internal list
    throw new Exception("Couldn't match any values from CiviCRM to Navision BEFORE data.");
  }

  /**
   * @param $nav_data
   * @param $civi_query_result
   *
   * @return bool
   */
  private function compare_data($nav_data, $civi_query_result) {
    foreach ($nav_data as $nav_civi_key => $nav_value) {
      // FIXME: is rtrim necessary here? Sometimes data from navision seems to be fucked with spaces in the end
      if (strcasecmp($civi_query_result[$nav_civi_key], rtrim($nav_value, " ")) != 0) {
        // extra check for country_id - option Value Lookup
        if ($nav_civi_key == "country_id") {
          $result = civicrm_api3('Country', 'getsingle', array(
            'sequential' => 1,
            'id' => $civi_query_result[$nav_civi_key],
          ));
          if (strcasecmp($result['iso_code'], $nav_value) == 0) {
            continue;
          }
        }
        $this->log("Value Mismatch - Nav Data: '$civi_query_result[$nav_civi_key]' != '{$nav_value}' (CiviData)");
        return FALSE;
      }
    }
    return TRUE;
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

    $this->create_linked_organisation_address($contact_id);

    $address = $this->record->get_civi_individual_address();
    $address['contact_id'] = $contact_id;
    $this->create_civi_entity($address, 'Address');

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

  /**
   * @param $contact_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function remove_old_linked_organisation_address($contact_id) {
    $company_data_before = $this->record->get_company_data('before');
    if (empty($company_data_before) || !$this->record->company_changed()) {
      return;
    }
    $org_contact_id = $this->get_contact_id_from_nav_id($company_data_before['Org_nav_id']);
    if (empty($org_contact_id)) {
      return;
    }
    $relationship_id = $this->get_civi_relationship_id($contact_id, $org_contact_id);
    if (empty($relationship_id)) {
      $this->log("Couldn't disable Relationship for user {$contact_id} and organisation {$org_contact_id}");
      return;
    }
    $this->disable_relationship($relationship_id);
    $address_id = $this->get_entity_id($company_data_before['Address'], $contact_id, 'Address');
    if (empty($address_id)) {
      return;
    }
    $this->delete_entity($address_id, 'Address');
  }

  /**
   * checks if the Organisation is found via company Nav ID
   *     if not, create company and address
   * adds address to contact as a shared address
   * @param $contact_id
   *
   * @throws \Exception
   */
  private function create_linked_organisation_address($contact_id) {
    $company_data = $this->record->get_company_data();
    if (empty($company_data)) {
      return;
    }
    $org_contact_id = $this->get_contact_id_from_nav_id($company_data['Org_nav_id']);
    if (empty($org_contact_id)) {
      $org_contact_id = $this->create_civi_entity($company_data['Contact'], 'Contact');
      $company_data['Address']['contact_id'] = $org_contact_id;
      $address_id = $this->create_civi_entity($company_data['Address'], 'Address');
    } else {
      $address_id = $this->get_entity_id($company_data['Address'], $org_contact_id, 'Address');
    }
    $company_data['Address']['contact_id'] = $contact_id;
    $company_data['Address']['master_id'] = $address_id;
    // check if address is already available on contact
    $new_shared_address_id = $this->get_entity_id($company_data['Address'], $contact_id, 'Address');
    if (empty($new_shared_address_id)) {
      $new_shared_address_id = $this->create_civi_entity($company_data['Address'], 'Address');
    }
    return $new_shared_address_id;
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

  private function check_new_record() {
    return $this->record->get_change_type() == 'New';
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
    $changed_civi_entities[] = $this->record->get_changed_Address_values($type);
    $changed_civi_entities[] = $this->record->get_changed_Phone_values($type);
    $changed_civi_entities[] = $this->record->get_changed_Email_values($type);
    $changed_civi_entities[] = $this->record->get_changed_Website_values($type);

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
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

/**
 * Class CRM_Nav_Data_EntityData_Contact
 */
class CRM_Nav_Data_EntityData_Contact  extends CRM_Nav_Data_EntityData_Base {

  // civi data structures
  private $_individual_before;
  private $_individual_after;
  private $_organisation_before;
  private $_organisation_after;

  private $_navision_id;
  private $_nav_custom_field;
  private $_organisation_id;

  private $_is_organization;

  // ['emails' => 'before|after'] => xx, 'Contact' => 'before|after'] => xx]
  private $_lookup_data;

  // Civi Data
  private $civi_contact_data;

  /**
   * CRM_Nav_Data_EntityData_Contact constructor.
   *
   * @param $before_individual
   * @param $after_individual
   * @param $before_company
   * @param $after_company
   * @param $nav_id
   * @param $lookup_data
   * @param $is_organization
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($before_individual, $after_individual, $before_company, $after_company, $nav_id, $lookup_data, $is_organization) {
    $this->_individual_before   = $before_individual;
    $this->_individual_after    = $after_individual;
    $this->_organisation_before = $before_company;
    $this->_organisation_after  = $after_company;
    $this->_navision_id         = $nav_id;
    $this->_lookup_data         = $lookup_data;
    $this->_is_organization     = $is_organization;
    $this->_nav_custom_field    = CRM_Nav_Config::get('navision_custom_field');

    $this->get_civi_ids();
    $this->get_civi_data();
  }

  /**
   * @return mixed
   */
  public function get_contact_id() {
    if (empty($this->_contact_id)) {
      // double check - in case an earlier record created the contact!
      $this->get_civi_ids();
    }
    return $this->_contact_id;
  }

  /**
   * @return mixed
   */
  public function get_org_id() {
    if (empty($this->_organisation_id)) {
      // double check - in case an earlier record created the organisation!
      $this->get_civi_ids();
    }
    return $this->_organisation_id;
  }

  public function is_organization() {
    return $this->_is_organization;
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function update() {
    if (empty($this->conflict_data['updates'])) {
      return;
    }
    $values = $this->conflict_data['updates'];
    $values['id'] = $this->_contact_id;
    $this->create_entity('Contact', $values);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function apply_changes() {
    if (empty($this->conflict_data['valid_changes'])) {
      return;
    }
    $values = $this->conflict_data['valid_changes'];
    $values['id'] = $this->_contact_id;
    $this->create_entity('Contact', $values);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function delete() {
    if (!empty($this->delete_data['individual'])) {
      $values = $this->delete_data['individual'];
      foreach ($values as $key => $val) {
        // set to empty
        $values[$key] = '';
      }
      $values['id'] = $this->_contact_id;
      $this->create_entity('Contact', $values);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function i3val() {
    if (!empty($this->conflict_data['i3val'])) {
      $values = $this->conflict_data['i3val'];
      $values['id'] = $this->_contact_id;
      $this->i3val_update($values);
    }
  }

  /**
   *
   */
  public function calc_differences() {
    // get changed stuff
    $this->changed_data['individual'] = $this->compare_data_arrays($this->_individual_before, $this->_individual_after);
    // deleted stuff
    $this->delete_data['individual'] = $this->compare_delete_data($this->_individual_before, $this->_individual_after);

    $this->check_if_value_is_deleted($this->changed_data['individual'], $this->delete_data['individual']);
    $this->conflict_data = $this->compare_conflicting_data(
      $this->civi_contact_data, $this->_individual_before,
      $this->changed_data['individual'], 'Contact'
      );
    $this->find_missing_update_data();
  }

  /**
   * Checks for Data that is equal in before and after, but not present in
   * CiviCRM. If that's the case, it is added to the update array
   * and will be added
   */
  private function find_missing_update_data() {
    foreach ($this->_individual_after as $key => $value) {
      // Check if we have a value in after, but not value in civi yet,
      // then add to updates
      if (isset($this->civi_contact_data[$key]) && empty($this->civi_contact_data[$key]) && !empty($value)) {
        $this->conflict_data['updates'][$key] = $value;
      }
    }
  }

  /**
   * create Contact for Person, and if set for Company as well
   * @throws \CiviCRM_API3_Exception
   */
  public function create_full() {
    $this->_contact_id      = $this->create_entity('Contact', $this->_individual_after)['id'];
    $this->create_consent_record($this->_contact_id);
  }

  /**
   * @return mixed
   */
  public function get_nav_id() {
    return $this->_navision_id;
  }

  /**
   * @param $contact_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function create_consent_record($contact_id) {
    if (empty($contact_id)) {
      $this->log("No contact_id given, couldn't create consent record");
    }
    $result = CRM_Nav_Utils::civicrm_nav_api('ConsentRecord', 'create', array(
      'sequential' => 1,
      'contact_id' => $contact_id,
      'category' => "10",
      'source' => 1,
      'note' => "NAV Schnittstelle",
    ));
    if ($result['is_error'] == '1') {
      $this->log("Failed to create Consent record for Contact {$contact_id}");
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private function get_civi_ids() {
    // Lookup via Navision_id
    $contact_id = $this->get_contact_by_navision_id();
    // lookup via ID Tracker
    if (empty($contact_id)) {
      $contact_id = $this->find_contact_by_id_tracker();
    }
    // Lookup via emails / first_name/last_name
    if (empty($contact_id)) {
      $contact_id = $this->get_contact_by_data();
    }
    $this->_contact_id = $contact_id;

    // Organization
    if ($this->_organisation_before[$this->_nav_custom_field] == $this->_navision_id &&
      $this->_organisation_after[$this->_nav_custom_field] == $this->_navision_id
    ) {
      // We don't have a connected Company
      return;
    }
    // Check after Values for Org ID if nav ID is a company ID (diff than person NavID)
    if (isset($this->_organisation_after[$this->_nav_custom_field]) && !empty($this->_organisation_after[$this->_nav_custom_field]) &&
      $this->_organisation_after[$this->_nav_custom_field] != $this->_navision_id)
    {
      $values = [$this->_nav_custom_field => $this->_organisation_after[$this->_nav_custom_field],];
      $result = $this->get_entity('Contact', $values);
      if ($result['count'] == '0') {
        return;
      }
      if ($result['count'] != '1') {
        $this->log("Didn't find contactId for {$this->_navision_id}. Found {$result['count']} contacts.");
      } else {
        $this->_organisation_id = $result['id'];
        return;
      }
    }

    // TODO: Is this needed ?
    // Check before Values for Org ID if nav ID is a company ID (diff than person NavID)
    if (isset($this->_organisation_before[$this->_nav_custom_field]) && !empty($this->_organisation_before[$this->_nav_custom_field]) &&
      $this->_organisation_before[$this->_nav_custom_field] != $this->_navision_id)
    {
      $values = [$this->_nav_custom_field => $this->_organisation_before[$this->_nav_custom_field],];
      $result = $this->get_entity('Contact', $values);
      if ($result['count'] == '0') {
        return;
      }
      if ($result['count'] != '1') {
        $this->log("Didn't find contactId for {$this->_navision_id}. Found {$result['count']} contacts.");
      } else {
        $this->_organisation_id = $result['id'];
      }
    }
  }

  private function get_contact_by_data() {
    $email_contact_ids = $this->get_contact_ids_via_emails($this->_lookup_data['Emails']['before']);
    if (empty($email_contact_ids)) {
      $email_contact_ids = $this->get_contact_ids_via_emails($this->_lookup_data['Emails']['after']);
    } // TODO: check if only one contact is in here - we found contact then!
    if (count($email_contact_ids) == '1') {
      return reset($email_contact_ids);
    }
    return $this->get_contact_via_emails($email_contact_ids, $this->_lookup_data['Contact']);
  }

  /**
   * @param $emails
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function get_contact_ids_via_emails($emails) {
    if (empty($emails)) {
      return array();
    }
    $result = CRM_Nav_Utils::civicrm_nav_api('Email', 'get', array(
      'sequential' => 1,
      'email' => array('IN' => $emails),
    ));
    if ($result['is_error'] == '1') {
      $this->log("Api command to get Emails Entities failed. Reason: {$result['error_message']}");
      return array();
    }
    $contact_ids = [];
    foreach ($result['values'] as $val) {
      $contact_ids[] = $val['contact_id'];
    }
    // filter out deleted contcts etc ...
    $result_ids = CRM_Nav_Utils::civicrm_nav_api('Contact', 'get', array(
      'sequential' => 1,
      'return' => array("id"),
      'id' => array('IN' => array(1, 2, 3)),
    ));
    $res_ids = [];
    foreach ($result_ids['values'] as $val) {
      $res_ids[] = $val['contact_id'];
    }
    return $res_ids;
  }

  /**
   * @param $contact_ids      (array with contact_ids from email lookup)
   * @param $contact_details  (array first_name, last_name)
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  private function get_contact_via_emails($contact_ids, $contact_details) {
    if (!empty($contact_ids)) {
      $lookup_details['id'] = array('IN' => $contact_ids);
    }

    if (!empty($contact_details['before']['first_name']) || empty(!$contact_details['before']['last_name'])) {
      $lookup_details = $contact_details['before'];
    }
    if (!empty($contact_details['after']['first_name']) || empty(!$contact_details['after']['last_name'])) {
      foreach ($contact_details['after'] as $key => $value) {
        $lookup_details[$key] = $value;
      }
      $lookup_details = $contact_details['after'];
    }
    $result = CRM_Nav_Utils::civicrm_nav_api('Contact', 'get', $lookup_details);
    if ($result['is_error'] == '1') {
      $this->log("Error occured while looking up contacts. Message: " . $result['error_message']);
      return "";
    }
    if ($result['count'] != '1') {
      $this->log("Found {$result['count']} entries for contact.");
      return "";
    }
    return $result['id'];
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  protected function get_civi_data() {
    if (empty($this->_contact_id)) {
      return;
    }
    $result = CRM_Nav_Utils::civicrm_nav_api('Contact', 'get', array(
      'sequential' => 1,
      'id' => $this->_contact_id,
      'return' => array("id",
                        $this->_nav_custom_field,
                        'first_name',
//                        'middle_name',
                        'last_name',
                        'formal_title',
                        'job_title',
                        'birth_date',
                        'prefix_id'
        ),
    ));
    if ($result['is_error']) {
      $this->log("Couldn't get civi data for Contact {$this->_contact_id}. Error: {$result['error_message']}");
      // TODO: throw Exception?
    }
    if ($result['count'] != '1') {
      $this->log("Couldn't get civi data for Contact {$this->_contact_id}. Found {$result['count']} Contacts");
    }
    $this->civi_contact_data = $result['values']['0'];
  }

  /**
   * @return string|void
   * @throws \CiviCRM_API3_Exception
   */
  private function get_contact_by_navision_id() {
    $values = [$this->_nav_custom_field => $this->_navision_id,];
    $result = $this->get_entity('Contact', $values);

    if ($result['count'] > '1') {
      $this->log("Didn't find contactId for {$this->_navision_id}. Found {$result['count']} contacts.");
      return;
    }
    if ($result['count'] == '0') {
      return '';
    } else {
      return $result['id'];
    }
  }

  /**
   * @param $navision_id
   *
   * @return string
   */
  private function find_contact_by_id_tracker() {
    $result = CRM_Nav_Utils::civicrm_nav_api('Contact', 'findbyidentity', [
      'identifier' => $this->_navision_id,
      'identifier_type' => "navision",
    ]);
    if ($result['count'] == '1') {
      return $result['id'];
    }
    return "";
  }
}
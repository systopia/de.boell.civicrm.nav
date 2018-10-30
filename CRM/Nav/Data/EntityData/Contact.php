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

  private $_nav_data_record;

  // ['emails' => xx, 'Contact' => xx]
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
   * @param $parent
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($before_individual, $after_individual, $before_company, $after_company, $nav_id, $lookup_data, &$parent) {
    $this->_individual_before   = $before_individual;
    $this->_individual_after    = $after_individual;
    $this->_organisation_before = $before_company;
    $this->_organisation_after  = $after_company;
    $this->_navision_id         = $nav_id;
    $this->_lookup_data         = $lookup_data;
    $this->_nav_data_record     = $parent;
    $this->_nav_custom_field    = CRM_Nav_Config::get('navision_custom_field');

    $this->get_civi_ids();
    $this->get_civi_data();
  }

  /**
   * @return mixed
   */
  public function get_contact_id() {
    return $this->_contact_id;
  }

  /**
   * @return mixed
   */
  public function get_org_id() {
    return $this->_organisation_id;
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
    if (!empty($this->delete_data['organization'])) {
      $values = $this->delete_data['organization'];
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
    if (empty($this->conflict_data['i3val'])) {
      return;
    }
    $this->i3val_update($this->conflict_data['i3val']);
  }

  /**
   *
   */
  public function calc_differences() {
    // get changed stuff
    $this->changed_data['individual'] = $this->compare_data_arrays($this->_individual_before, $this->_individual_after);
    $this->changed_data['organization'] = $this->compare_data_arrays($this->_organisation_before, $this->_organisation_after);
    // deleted stuff
    $this->delete_data['individual'] = $this->compare_delete_data($this->_individual_before, $this->_individual_after);
    $this->delete_data['organization'] = $this->compare_delete_data($this->_organisation_before, $this->_organisation_after);
    // conflicting stuff (only for individual - we shoudln't change company values here!
    $this->conflict_data = $this->compare_conflicting_data(
      $this->civi_contact_data, $this->_individual_before,
      $this->changed_data['individual'], 'Contact'
      );
  }

  /**
   * create Contact for Person, and if set for Company as well
   */
  public function create_full() {
    $this->_contact_id      = $this->create_entity('Contact', $this->_individual_after)['id'];
    $this->_organisation_id = $this->create_entity('Contact', $this->_organisation_after)['id'];
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private function get_civi_ids() {
    $values = [$this->_nav_custom_field => $this->_navision_id,];
    $result = $this->get_entity('Contact', $values);

    if ($result['count'] > '1') {
      $this->log("Didn't find contactId for {$this->_navision_id}. Found {$result['count']} contacts.");
      return;
    }
    if ($result['count'] == '0') {
      $this->_contact_id = '';
    } else {
      $this->_contact_id = $result['id'];
    }

    // Organization
    if ($this->_organisation_before[$this->_nav_custom_field] == $this->_navision_id &&
      $this->_organisation_after[$this->_nav_custom_field] == $this->_navision_id
    ) {
      // We don't have a connected Company
      return;
    }
    // Check before Values for Org ID if nav ID is a company ID (diff than person NavID)
    if (isset($this->_organisation_before[$this->_nav_custom_field]) &&
      $this->_organisation_before[$this->_nav_custom_field] != $this->_navision_id)
    {
      $values = [$this->_nav_custom_field => $this->_organisation_before[$this->_nav_custom_field],];
      $result = $this->get_entity('Contact', $values);
      if ($result['count'] == '0') {
        return;
      }
      if ($result['count'] != '1') {
        $this->log("Didn't find contactId for {$this->_navision_id}. Found {$result['count']} contacts.");
        return;
      }
      $this->_organisation_id = $result['id'];
    }

    // Check after Values for Org ID if nav ID is a company ID (diff than person NavID)
    if (isset($this->_organisation_after[$this->_nav_custom_field]) &&
      $this->_organisation_after[$this->_nav_custom_field] != $this->_navision_id)
    {
      $values = [$this->_nav_custom_field => $this->_organisation_after[$this->_nav_custom_field],];
      $result = $this->get_entity('Contact', $values);
      if ($result['count'] == '0') {
        return;
      }
      if ($result['count'] != '1') {
        $this->log("Didn't find contactId for {$this->_navision_id}. Found {$result['count']} contacts.");
        return;
      }
      $this->_organisation_id = $result['id'];
    }
  }

  /**
   * If contact couldn't be identified by NavId, it will be identified by email(s)
   * and first_name and last name. If that's not available, a new contact is created
   * with the AFTER values and ALL provided values
   * @param $contact_id
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public function get_or_create_contact() {
    if (!empty($this->_contact_id)) {
      return $this->_contact_id;
    }
    $email_contact_ids = $this->get_contact_ids_via_emails($this->_lookup_data['Emails']);
    $lookup_contact_id = $this->get_contact_via_emails($email_contact_ids, $this->_lookup_data['Contact']);
    if ($lookup_contact_id == "") {
      $this->_nav_data_record->create_full_contact();
      return '-1';
    }
    return $lookup_contact_id;
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

  /**
   * @param $contact_ids      (array with contact_ids from email lookup)
   * @param $contact_details  (array first_name, last_name)
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  private function get_contact_via_emails($contact_ids, $contact_details) {
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

  /**
   * @throws \CiviCRM_API3_Exception
   */
  protected function get_civi_data() {
    if (empty($this->_contact_id)) {
      return;
    }
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'id' => $this->_contact_id,
      'return' => array("id",
                        $this->_nav_custom_field,
                        'first_name',
                        'middle_name',
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


}
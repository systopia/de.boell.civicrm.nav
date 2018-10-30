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

class CRM_Nav_Data_EntityData_Email  extends CRM_Nav_Data_EntityData_Base {

  private $_email_org;
  private $_email_priv;
  private $_email_priv_2;
  private $_location_type_private;
  private $_location_type_organization;

  private $civi_email_org;
  private $civi_email_priv;
  private $civi_email_priv_2;

  public function __construct($email_1_org, $email_priv, $email_2_priv,
                              $private_location_type, $organization_location_type, $contact_id) {
    $this->_email_org                  = $email_1_org;
    $this->_email_priv                 = $email_priv;
    $this->_email_priv_2               = $email_2_priv;
    $this->_location_type_private      = $private_location_type;
    $this->_location_type_organization = $organization_location_type;

    $this->_contact_id = $contact_id;

    $this->get_civi_data();
  }

  public function calc_differences() {
    $emails = $this->iterate_all_emails();
    foreach ($emails as $email) {
      if (empty($email)) {
        continue;
      }
      $tmp_changed_data = $this->compare_data_arrays($email['before'], $email['after']);
      if (!empty($tmp_changed_data)) {
        $this->changed_data[] = $email['after'];
        $tmp_changed_data = $email['after']; // for later we need the whole entity
      }
      $delete_data = $this->compare_delete_data($email['before'], $email['after']);
      if (!empty($delete_data)) {
        $this->delete_data[] = $email['before'];
      }
      $civi_data = $this->get_civi_email($email);
      $this->conflict_data[] = $this->compare_conflicting_data(
        $civi_data, $email['before'],
        $tmp_changed_data, 'Email'
      );
    }
  }

  public function create_full($contact_id) {
    foreach ($this->iterate_values('after') as $email_value) {
      $email_value['contact_id'] = $contact_id;
      $this->create_entity('Email', $email_value);
    }
  }

  protected function get_civi_data() {
    if (empty($this->_contact_id)) {
      return;
    }
    $result = civicrm_api3('Email', 'get', array(
      'sequential' => 1,
      'contact_id' => $this->_contact_id,
      'return' => array("email", "location_type_id"),
    ));
    if ($result['is_error']) {
      $this->log("Couldn't get civi data for Email {$this->_contact_id}. Error: {$result['error_message']}");
      // TODO: throw Exception?
    }
    foreach ($result['values'] as $civi_email) {
      $this->map_email($civi_email);
    }
  }

  private function iterate_all_emails() {
    $result = [];
    $result[] = $this->_email_org;
    $result[] = $this->_email_priv;
    $result[] = $this->_email_priv_2;
    return $result;
  }

  private function get_civi_email($nav_email) {
    foreach ($this->iterate_civi_emails() as $email) {
      if (isset($nav_email['before']) && $email['email'] == $nav_email['before']['email']) {
        return $email;
      }
      if (isset($nav_email['after']) && $email['email'] == $nav_email['after']['email']) {
        return $email;
      }
    }
    return [];
  }

  private function iterate_civi_emails() {
    $result = [];
    $result[] = $this->civi_email_org;
    $result[] = $this->civi_email_priv;
    $result[] = $this->civi_email_priv_2;
    return $result;
  }

  private function iterate_values($type) {
    $result = [];
    if(isset($this->_email_org[$type])) {
      $result[] = $this->_email_org[$type];
    }
    if(isset($this->_email_priv[$type])) {
      $result[] = $this->_email_priv[$type];
    }
    if(isset($this->_email_priv_2[$type])) {
      $result[] = $this->_email_priv_2[$type];
    }
    return $result;
  }

  private function map_email($email){
    if ($email['location_type_id'] == $this->_location_type_private) {
      if ($email['email'] == $this->_email_priv['before']['email']) {
        $this->civi_email_priv = $email;
        return;
      }
      if ($email['email'] == $this->_email_priv['after']['email']) {
        $this->civi_email_priv = $email;
        return;
      }
      if ($email['email'] == $this->_email_priv_2['before']['email']) {
        $this->civi_email_priv_2 = $email;
        return;
      }
      if ($email['email'] == $this->_email_priv_2['after']['email']) {
        $this->civi_email_priv_2 = $email;
        return;
      }
      // Couldn't be mapped. Maybe it's an Email besides the ones from Navision
      return;
    }
    if ($email['location_type_id'] == $this->_location_type_organization) {
      if ($email['email'] == $this->_email_org['before']['email']) {
        $this->civi_email_org = $email;
        return;
      }
      if ($email['email'] == $this->_email_org['after']['email']) {
        $this->civi_email_org = $email;
        return;
      }
      // Couldn't be mapped. Maybe it's an Email besides the ones from Navision
      return;
    }
  }
}
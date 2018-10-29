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

  private $_email_org_1_before;
  private $_email_org_1_after;
  private $_email_priv_before;
  private $_email_priv_after;
  private $_email_priv_2_before;
  private $_email_priv_2_after;
  private $_location_type_private;
  private $_location_type_organization;
  private $_contact_id;

  private $civi_email_org_1;
  private $civi_email_priv;
  private $civi_email_priv_2;

  public function __construct($email_1_org, $email_priv, $email_2_priv,
                              $private_location_type, $organization_location_type, $contact_id) {
    $this->_email_org_1_before         = $email_1_org['before'];
    $this->_email_org_1_after          = $email_1_org['after'];
    $this->_email_priv_before          = $email_priv['before'];
    $this->_email_priv_after           = $email_priv['after'];
    $this->_email_priv_2_before        = $email_2_priv['before'];
    $this->_email_priv_2_after         = $email_2_priv['after'];
    $this->_location_type_private      = $private_location_type;
    $this->_location_type_organization = $organization_location_type;

    $this->_contact_id = $contact_id;

    $this->get_civi_data();
  }

  protected function get_civi_data() {
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

  private function map_email($email){
    if ($email['location_type_id'] == $this->_location_type_private) {
      if ($email['email'] == $this->_email_priv_before['email']) {
        $this->civi_email_priv = $email;
      }
      if ($email['email'] == $this->_email_priv_2_before['email']) {
        $this->civi_email_priv_2 = $email;
      }
      return;
    }
    if ($email['location_type_id'] == $this->_location_type_organization) {
      if ($email['email'] == $this->_email_org_1_before['email']) {
        $this->civi_email_org_1 = $email;
      }
      return;
    }
  }
}
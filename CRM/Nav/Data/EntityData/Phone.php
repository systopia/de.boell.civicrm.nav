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

class CRM_Nav_Data_EntityData_Phone  extends CRM_Nav_Data_EntityData_Base {

  // all values with before and after
  private $_phone_org;
  private $_mobile_org;
  private $_fax_org;
  private $_phone_priv;
  private $_mobile_priv;
  private $_fax_priv;
  private $_location_type_private;
  private $_location_type_organization;

  private $civi_phone_org;
  private $civi_mobile_org;
  private $civi_fax_org;
  private $civi_phone_priv;
  private $civi_mobile_priv;
  private $civi_fax_priv;


  public function __construct($phone_org, $mobile_org, $fax_org,
                              $phone_priv, $mobile_priv, $fax_priv,
                              $private_location_type, $organization_location_type,
                              $contact_id) {
    $this->_phone_org = $phone_org;
    $this->_mobile_org = $mobile_org;
    $this->_fax_org = $fax_org;
    $this->_phone_priv = $phone_priv;
    $this->_mobile_priv = $mobile_priv;
    $this->_fax_priv = $fax_priv;
    $this->_contact_id = $contact_id;

    $this->_location_type_private = $private_location_type;
    $this->_location_type_organization = $organization_location_type;

    $this->get_civi_data();
  }

  public function create_full($contact_id) {
    foreach ($this->iterate_values('after') as $phone_value) {
      $phone_value['contact_id'] = $contact_id;
      $this->create_entity('Phone', $phone_value);
    }
  }

  protected function get_civi_data() {
    if (empty($this->_contact_id)) {
      return;
    }
    $result = civicrm_api3('Phone', 'get', array(
      'sequential' => 1,
      'contact_id' => $this->_contact_id,
      'return' => ["phone",
                   "location_type_id",
                   "phone_type_id"],
    ));
    if ($result['is_error']) {
      $this->log("Couldn't get civi data for Phone {$this->_contact_id}. Error: {$result['error_message']}");
      // TODO: throw Exception?
    }
    foreach ($result['values'] as $civi_phone) {
      $this->assign_civi_phone_type($civi_phone);
    }
  }

  private function iterate_values($type) {
    $result = [];
    if(isset($this->_phone_org[$type])) {
      $result[] = $this->_phone_org[$type];
    }
    if(isset($this->_mobile_org[$type])) {
      $result[] = $this->_mobile_org[$type];
    }
    if(isset($this->$fax_org[$type])) {
      $result[] = $this->_fax_org[$type];
    }
    if(isset($this->_phone_priv[$type])) {
      $result[] = $this->_phone_priv[$type];
    }
    if(isset($this->_mobile_priv[$type])) {
      $result[] = $this->_mobile_priv[$type];
    }
    if(isset($this->_fax_priv[$type])) {
      $result[] = $this->_fax_priv[$type];
    }
    return $result;
  }

  private function assign_civi_phone_type($civi_phone_data) {
    switch ($civi_phone_data['phone_type_id']) {
      case CRM_Nav_Config::get('Phone'):
        if ($civi_phone_data['location_type_id'] == $this->_location_type_private) {
          $this->civi_phone_priv = $civi_phone_data;
          break;
        }
        if($civi_phone_data['location_type_id'] == $this->_location_type_organization) {
          $this->civi_phone_org = $civi_phone_data;
          break;
        }
      case CRM_Nav_Config::get('Mobile'):
        if ($civi_phone_data['location_type_id'] == $this->_location_type_private) {
          $this->civi_mobile_priv = $civi_phone_data;
          break;
        }
        if($civi_phone_data['location_type_id'] == $this->_location_type_organization) {
          $this->civi_mobile_org = $civi_phone_data;
          break;
        }
      case CRM_Nav_Config::get('Fax'):
        if ($civi_phone_data['location_type_id'] == $this->_location_type_private) {
          $this->civi_fax_priv = $civi_phone_data;
          break;
        }
        if($civi_phone_data['location_type_id'] == $this->_location_type_organization) {
          $this->civi_fax_org = $civi_phone_data;
          break;
        }
      default:
        $this->log("Invalid Phone_type ID {$civi_phone_data['phone_type_id']} for Contact {$this->_contact_id}");
    }
  }

}
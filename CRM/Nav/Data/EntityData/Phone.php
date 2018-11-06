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
 * Class CRM_Nav_Data_EntityData_Phone
 */
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


  /**
   * CRM_Nav_Data_EntityData_Phone constructor.
   *
   * @param $phone_org
   * @param $mobile_org
   * @param $fax_org
   * @param $phone_priv
   * @param $mobile_priv
   * @param $fax_priv
   * @param $private_location_type
   * @param $organization_location_type
   * @param $contact_id
   */
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

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function update() {
    foreach ($this->conflict_data as $conflict) {
      if (empty($conflict['updates'])) {
        continue;
      }
      $values = $conflict['updates'];
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Phone', $values);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function apply_changes() {
    foreach ($this->conflict_data as $conflict) {
      if (empty($conflict['valid_changes'])) {
        continue;
      }
      $values = $conflict['valid_changes'];
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Phone', $values);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function i3val() {
    foreach ($this->conflict_data as $conflict) {
      if (empty($conflict['i3val'])) {
        continue;
      }
      $this->i3val_update($conflict['i3val']);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function delete() {
    foreach ($this->delete_data as $del_data) {
      if (empty($del_data)) {
        continue;
      }
      $this->delete_entity('Phone', $del_data['id']);
    }
  }

  /**
   *
   */
  public function calc_differences() {
    $phones = $this->iterate_all_phones();
    foreach ($phones as $phone) {
      if (empty($phone)) {
        continue;
      }
      $tmp_changed_data = $this->compare_data_arrays($phone['before'], $phone['after']);
      if (!empty($tmp_changed_data) || (empty($this->get_civi_phone($phone)) && !empty($phone['after']))) {
        $this->changed_data[] = $phone['after'];
        $tmp_changed_data = $phone['after']; // for later we need the whole entity
      }
      $delete_data = $this->compare_delete_data($phone['before'], $phone['after']);
      if (!empty($delete_data)) {
        $civi_data = $this->get_civi_phone($phone);
        $this->delete_data[] = $civi_data;
      }
      $civi_data = $this->get_civi_phone($phone);
      $this->conflict_data[] = $this->compare_conflicting_data(
        $civi_data, $phone['before'],
        $tmp_changed_data, 'Phone'
      );
    }
  }

  /**
   * @param $contact_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function create_full($contact_id) {
    foreach ($this->iterate_values('after') as $phone_value) {
      $phone_value['contact_id'] = $contact_id;
      $this->create_entity('Phone', $phone_value);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
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

  /**
   * @param $nav_phone
   *
   * @return array|mixed
   */
  private function get_civi_phone($nav_phone) {
    foreach ($this->iterate_civi_phones() as $phone) {
      if (isset($nav_phone['before']) && $phone['phone'] == $nav_phone['before']['phone']) {
        return $phone;
      }
      if (isset($nav_phone['after']) && $phone['phone'] == $nav_phone['after']['phone']) {
        return $phone;
      }
    }
    return [];
  }

  /**
   * @return array
   */
  private function iterate_all_phones() {
    $result = [];
    $result[] = $this->_phone_org;
    $result[] = $this->_mobile_org;
    $result[] = $this->_fax_org;
    $result[] = $this->_phone_priv;
    $result[] = $this->_mobile_priv;
    $result[] = $this->_fax_priv;
    return $result;
  }

  /**
   * @return array
   */
  private function iterate_civi_phones() {
    $result = [];
    $result[] = $this->civi_phone_org;
    $result[] = $this->civi_mobile_org;
    $result[] = $this->civi_fax_org;
    $result[] = $this->civi_phone_priv;
    $result[] = $this->civi_mobile_priv;
    $result[] = $this->civi_fax_priv;
    return $result;
  }

  /**
   * @param $type
   *
   * @return array
   */
  private function iterate_values($type) {
    $result = [];
    if(isset($this->_phone_org[$type])) {
      $result[] = $this->_phone_org[$type];
    }
    if(isset($this->_mobile_org[$type])) {
      $result[] = $this->_mobile_org[$type];
    }
    if(isset($this->_fax_org[$type])) {
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

  /**
   * @param $civi_phone_data
   */
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
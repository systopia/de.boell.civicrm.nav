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

abstract  class CRM_Nav_Data_EntityData_Base {

  protected $_contact_id;

  protected $changed_data;
  protected $delete_data;
  protected $conflict_data;

  /**
   * Fetch Civi Data for Entity for this->_contact_id
   */
  abstract protected function get_civi_data();

  /**
   * Update Values/Entities in civiCRM based on calc_differences()
   */
  abstract public function update();

  /**
   * Applies changes in civiCRM based on calc_differences()
   */
  abstract public function apply_changes();

  /**
   * delete values/Entities from CiviCRM based on calc_differences()
   */
  abstract public function delete();

  /**
   * Calculates differences between before/after, setting data for update,
   * apply_cahnges and possibly i3Val
   */
  abstract public function calc_differences();

  /**
   * @param $message
   */
  protected function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

  /**
   * Compares before and after civi values, called appropriatly from sub-classes
   * @param $before
   * @param $after
   *
   * @return array
   */
  protected function compare_data_arrays($before, $after) {
    $changes = [];
    foreach ($after as $key => $value) {
      if (!isset($before[$key])) {
        $changes[$key] = $value;
        continue;
      }
      if ($value != $before[$key]) {
        $changes[$key] = $value;
      }
    }
    return $changes;
  }

  /**
   * Check for deleted data. Data needs to be deleted from civi
   * if Value is in before, but not after
   * @param $before
   * @param $after
   *
   * @return array
   */
  protected function compare_delete_data($before, $after) {
    $delete_data = [];
    foreach ($before as $key => $value) {
      if (empty($after[$key]) && !empty($value)) {
        $delete_data[$key] = $value;
      }
    }
    return $delete_data;
  }

  /**
   * Compare changed data to current civi Data. Updates occur when no value is in
   * Civi yet, or if the change value is already in civi (fill up data).
   * If the before value is in civi, a valid update operation occurs
   *
   * Entity IDs will be added to update/change values
   *
   * i3Val:
   * If non of the above, we have a conflict, possible values for all
   * Entities (update/change) will be rolled back, and all changed data is added
   * to i3Val result, and will be passed to request_update with contact_id
   *
   * !! Special handling for Phone and Email since whole entity needs to be updated/changed !!
   * !! Will be overwritten by Website.php --> Special handling needed !!
   * @param $civi_data
   * @param $before
   * @param $changed_data
   * @param $entity
   *
   * @return mixed
   */
  protected function compare_conflicting_data($civi_data, $before, $changed_data, $entity) {
    $i3val = [];
    $valid_changes = [];
    $update_values = [];
    foreach ($changed_data as $key => $value) {
      if (empty($civi_data[$key])) {
        if ($entity == 'Phone' || $entity == 'Email') {
          $update_values = $changed_data;
          break;
        } else {
          // $value can be updated
          $update_values[$key] = $value;
        }
        continue;
      }
      if ($civi_data[$key] == $value) {
        if ($entity == 'Phone' || $entity == 'Email') {
          $update_values = $changed_data;
          break;
        } else {
          $update_values[$key] = $value;
        }
        continue;
      }
      // check if nav changed data is different from civi data
      if ($civi_data[$key] != $value) {
        // check if $value matches before data
        if (isset($before[$key]) && $before[$key] == $civi_data[$key]) {
          // special behavior for Email, Phone
          // We always need the whole Entity if we have updates/ Valid changes
          if ($entity == 'Phone' || $entity == 'Email') {
            $valid_changes = $changed_data;
            break;
          } else {
            // data is same as before value --> valid change
            $valid_changes[$key] = $value;
          }

        } else {
          // i3Val Conflict
          if ($entity != 'Contact') {
            $i3val = $changed_data;
            unset ($valid_changes);
            unset ($update_values);
            break;
          } else {
            $i3val[$key] = $value;
          }
        }
      }
    }
    // add id to all results if they are not empty, not needed for i3Val
    if (!empty($update_values) && isset($civi_data['id'])) {
      $update_values['id'] = $civi_data['id'];
    }
    if (!empty($valid_changes) && isset($civi_data['id'])) {
      $valid_changes['id'] = $civi_data['id'];
    }
    $result['updates'] = $update_values;
    $result['valid_changes'] = $valid_changes;
    $result['i3val'] = $i3val;
    return $result;
  }

  /**
   * @param $entity
   * @param $values
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  protected function create_entity($entity, $values) {
    $result = civicrm_api3($entity, 'create', $values);
    if ($result['is_error']) {
      $this->log("Failed to create {$entity}-Entity with values " . json_encode($values));
    }
    return $result;
  }

  /**
   * @param $entity
   * @param $values
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function get_entity($entity, $values) {
    $result = civicrm_api3($entity, 'get', $values);
    if ($result['is_error']) {
      $this->log("Failed to get {$entity}-Entity with values " . json_encode($values));
    }
    return $result;
  }

  /**
   * @param $entity
   * @param $entity_id
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function delete_entity($entity, $entity_id) {
    $result = civicrm_api3($entity, 'delete', array(
      'sequential' => 1,
      'id' => $entity_id,
    ));
    if ($result['is_error']) {
      $this->log("Failed to delete {$entity}-Entity with values ID {$entity_id}");
    }
    return $result;
  }

  /**
   * @param $values
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function i3val_update($values) {
    $result = civicrm_api3('Contact', 'request_update', $values);
    if ($result['is_error']) {
      $this->log("i3Val Request update failed for values " . json_encode($values));
    }
    return $result;
  }

  /**
   * set contact ID and automatically trigger update of internal civiCRM values
   * @param $contact_id
   */
  public function set_contact_id($contact_id) {
    $this->_contact_id = $contact_id;
    $this->get_civi_data();
  }
}
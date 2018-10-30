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

  abstract protected function get_civi_data();

  abstract public function calc_differences();

    /**
     * @param $message
     */
  protected function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

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

  protected function compare_delete_data($before, $after) {
    $delete_data = [];
    foreach ($before as $key => $value) {
      if (empty($after[$key]) && !empty($value)) {
        $delete_data[$key] = $value;
      }
    }
    return $delete_data;
  }

  protected function compare_conflicting_data($civi_data, $before, $changed_data, $entity) {
    $i3val = [];
    $valid_changes = [];
    $update_values = [];
    foreach ($changed_data as $key => $value) {
      if (!isset($civi_data[$key])) {
        if ($entity == 'Phone' || $entity == 'Email' || $entity == 'Website') {
          $update_values[$key] = $changed_data;
          break;
        } else {
          // $value can be updated
          $update_values[$key] = $value;
        }
        continue;
      }
      if ($civi_data[$key] == $value) {
        if ($entity == 'Phone' || $entity == 'Email' || $entity == 'Website') {
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
        if (isset($before[$key]) && $before[$key] == $value) {
          // special behavior for Email, Phone and Website.
          // We always need the whole Entity if we have updates/ Valid changes
          if ($entity == 'Phone' || $entity == 'Email' || $entity == 'Website') {
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
    $result['i3Val'] = $i3val;
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

  protected function get_entity($entity, $values) {
    $result = civicrm_api3($entity, 'get', $values);
    if ($result['is_error']) {
      $this->log("Failed to get {$entity}-Entity with values " . json_encode($values));
    }
    return $result;
  }

  public function set_contact_id($contact_id) {
    $this->_contact_id = $contact_id;
  }
}
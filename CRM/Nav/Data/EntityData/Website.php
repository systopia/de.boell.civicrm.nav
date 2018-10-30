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

class CRM_Nav_Data_EntityData_Website  extends CRM_Nav_Data_EntityData_Base {

  private $_website_before;
  private $_website_after;

  // if website is empty, needs to be created with after values
  private $civi_website;

  public function __construct($web_before, $web_after, $contact_id) {
    $this->_website_before = $web_before;
    $this->_website_after = $web_after;
    $this->_contact_id = $contact_id;

    $this->get_civi_data();
  }

  public function update() {
    if (empty($this->conflict_data['updates'])) {
      return;
    }
    $values = $this->conflict_data['updates'];
    $values['contact_id'] = $this->_contact_id;
    $values['website_type_id'] = "Work";
    $this->create_entity('Website', $values);
  }

  public function apply_changes() {
    if (empty($this->conflict_data['valid_changes'])) {
      return;
    }
    $values = $this->conflict_data['valid_changes'];
    $values['contact_id'] = $this->_contact_id;
    $this->create_entity('Website', $values);
  }

  public function delete() {
    if (empty($this->delete_data)) {
      return;
    }
    $this->delete_entity('Website', $this->delete_data['id']);
  }

  public function i3val() {
    // not implemented for Website. Instead we create a new Website in case of conflict
  }

  public function calc_differences() {
    $this->changed_data = $this->compare_data_arrays($this->_website_before, $this->_website_after);
    $this->delete_data = $this->compare_delete_data($this->_website_before, $this->_website_after);
    $this->conflict_data= $this->compare_conflicting_data(
      $this->civi_website, $this->_website_before,
      $this->changed_data, 'Website'
    );
  }

  public function create_full($contact_id) {
    if (isset($this->_website_after)) {
      $website_data = $this->_website_after;
      $website_data['contact_id'] = $contact_id;
      $this->create_entity('Website', $website_data);
    }
  }

  protected function get_civi_data() {
    if (empty($this->_contact_id)) {
      return;
    }
    $values = [
      'sequential' => 1,
      'contact_id' => $this->_contact_id,
      'website_type_id' => "Work",
      'return' => ["website_type_id", "url", "contact_id"],
    ];
    // get Website(s) for contact, and if url is set in before values put that in as arg
    if (isset($this->_website_before['url'])) {
      $values['url'] = ['LIKE' => "%{$this->parse_civi_url($this->_website_before['url'])}"];
    }
    $result = civicrm_api3('Website', 'get', $values);
    if ($result['is_error']) {
      $this->log("Couldn't get civi data for Website {$this->_contact_id}. Error: {$result['error_message']}");
      return;
    }
    if ($result['count'] == '0') {
      // nothing found, get all websites for contact and compare to _website_after/before
      unset($values['url']);
      $result = civicrm_api3('Website', 'get', $values);
      if ($result['is_error']) {
        $this->log("Couldn't get civi data for Website {$this->_contact_id}. Error: {$result['error_message']}");
        return;
      }
    }

    // first check against after values
    foreach ($result['values'] as $civi_website_data) {
      if ($this->parse_civi_url($civi_website_data['url']) == $this->parse_civi_url($this->_website_after['url'])) {
        $this->civi_website = $civi_website_data;
        return;
      }
    }
    // then check against before values
    foreach ($result['values'] as $civi_website_data) {
      if ($this->parse_civi_url($civi_website_data['url']) == $this->parse_civi_url($this->_website_before['url'])) {
        $this->civi_website = $civi_website_data;
        return;
      }
    }
  }

  /**
   * Website needs special handling here, overwrites base function
   * @param $civi_data
   * @param $before
   * @param $changed_data
   * @param $entity (not needed here)
   *
   * @return mixed|void
   */
  protected function compare_conflicting_data($civi_data, $before, $changed_data, $entity) {
    $i3val = [];
    $valid_changes = [];
    $update_values = [];

    if (!isset($civi_data['url'])) {
      $update_values = $changed_data;
    } else {
      if ($this->parse_civi_url($civi_data['url']) == $this->parse_civi_url($changed_data['url'])) {
        $update_values = $changed_data;
      }
    }

    // check if nav changed data is different from civi data
    if ($this->parse_civi_url($civi_data['url']) != $this->parse_civi_url($changed_data['url'])) {
      // check if $value matches before data
      if (isset($before['url']) && $this->parse_civi_url($before['url']) == $this->parse_civi_url($civi_data['url'])) {
          $valid_changes = $changed_data;
      }
    }

    if (empty($update_values) && empty($valid_changes)) {
      // usually we would have an i3val problem here, but we just add the website to update
      $update_values = $changed_data;
    }
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

    private function parse_civi_url($url) {
      if(empty(parse_url($url, PHP_URL_HOST))) {
        return parse_url($url, PHP_URL_PATH);
      } else {
        return parse_url($url, PHP_URL_HOST);
      }
    }

}

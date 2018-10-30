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
      'return' => ["website_type_id", "url"],
    ];
    // get Website(s) for contact, and if url is set in before values put that in as arg
    if (isset($this->_website_before['url'])) {
      $values['url'] = ['LIKE' => "%{$this->_website_before['url']}"];
    }
    $result = civicrm_api3('Website', 'get', $values);
    if ($result['is_error']) {
      $this->log("Couldn't get civi data for Website {$this->_contact_id}. Error: {$result['error_message']}");
      // TODO: throw Exception?
    }
    if (!isset($this->_website_before['url'])) {
      foreach ($result['values'] as $civi_website_data) {
        if (strpos($civi_website_data['url'], $this->_website_after['url']) >= 0) {
          $this->civi_website = $civi_website_data;
          break;
        }
      }
    }
  }

}

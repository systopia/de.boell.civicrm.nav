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

class CRM_Nav_ChangeTracker_LogAnalyzeRunner {

  private $_entities = [
    'Address',
    'Contact',
    'CustomContact',
    'Email',
    'Phone',
    'Relationship',
    'Website',
  ];

  private $_timestamp;

  private $Contact;
  private $Address;
  private $Relationship;
  private $Phone;
  private $Email;
  private $Website;
  private $CustomContact;

  private $debug;

  public static $nav_id_cache;

  public function __construct($entity = [], $debug) {
    // parameter validation
    $this->verify_entities($entity);
    if (!empty($entity)) {
      $this->_entities = [$entity];
    }

    $this->debug = $debug;

    $this->_timestamp = CRM_Nav_Config::get_last_timestamp();
    if (empty($this->_timestamp)) {
      $this->_timestamp = date('Y-m-d G:i:s');
      CRM_Nav_Config::set_last_timestamp($this->_timestamp);
    }

    // initialize Data Objects
    $this->Contact = new CRM_Nav_ChangeTracker_ContactAnalyzer($this->_timestamp, $debug);
    $this->Address = new CRM_Nav_ChangeTracker_AddressAnalyzer($this->_timestamp, $debug);
    $this->Relationship = new CRM_Nav_ChangeTracker_RelationshipAnalyzer($this->_timestamp, $debug);
    $this->Phone = new CRM_Nav_ChangeTracker_PhoneAnalyzer($this->_timestamp, $debug);
    $this->Email = new CRM_Nav_ChangeTracker_EmailAnalyzer($this->_timestamp, $debug);
    $this->Website = new CRM_Nav_ChangeTracker_WebsiteAnalyzer($this->_timestamp, $debug);
    $this->CustomContact = new CRM_Nav_ChangeTracker_CustomContactAnalyzer($this->_timestamp, $debug);
  }

  public function process() {
    foreach ($this->_entities as $entity) {
      $this->{$entity}->run();
    }
    $this->create_studienwerk_data();
  }

  private function create_studienwerk_data() {
    $result_changed_data = [];
    foreach ($this->_entities as $entity) {
      $entity_changed_data = $this->{$entity}->get_changed_data();
      foreach ($entity_changed_data as $contact_id => $values) {
        $result_changed_data[$contact_id][$entity] = $values;
      }
    }
    return $result_changed_data;
  }


  private function verify_entities($entity) {
    if (empty($entity)) {
      return;
    }
    if (!in_array($entity, $this->_entities)) {
      throw new API_Exception("Invalid entity parameter {$entity}");
    }
  }


  public function get_stats() {
    return "Log Analyze Stats Runner - implement me!";
  }

}
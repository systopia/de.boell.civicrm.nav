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

class CRM_Nav_Data_EntityData_Contact  extends CRM_Nav_Data_EntityData_Base {

  // civi data structures
  private $_individual_before;
  private $_individual_after;
  private $_organisation_before;
  private $_organisation_after;

  private $_navision_id;
  private $_nav_custom_field;
  private $_contact_id;
  private $_organisation_id;

  // Civi Data
  private $civi_contact_data;

  public function __construct($before_individual, $after_individual, $before_company, $after_company, $nav_id) {
    $this->_individual_before   = $before_individual;
    $this->_individual_after    = $after_individual;
    $this->_organisation_before = $before_company;
    $this->_organisation_after  = $after_company;
    $this->_navision_id         = $nav_id;
    $this->_nav_custom_field    = CRM_Nav_Config::get('navision_custom_field');

    // get live civi data
//            return array('No', 'Type', 'First_Name', 'Middle_Name', 'Surname', 'Job_Title', 'Funktion', 'Salutation_Code', 'Geburtsdatum');
    $this->get_civi_ids();
    $this->get_civi_data();
  }

  public function get_contact_id() {
    return $this->_contact_id;
  }

  // Helper
  private function get_civi_ids() {
    $result = civicrm_api3('Contact', 'get', array(
      'sequential'             => 1,
      $this->_nav_custom_field => $this->_navision_id,
    ));
    if ($result['count'] != 1) {
      $this->_contact_id = "";
      $this->log("Didn't find contactId for {$this->_navision_id}. Found {$result['count']} contacts");
      // TODO: Create Contact now!! $_contact_id is needed

      return;
    }
    $this->_contact_id = $result['id'];

    // TODO: get organisation ID here as well
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  protected function get_civi_data() {
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
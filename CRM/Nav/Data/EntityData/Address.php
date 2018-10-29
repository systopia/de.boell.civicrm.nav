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

class CRM_Nav_Data_EntityData_Address  extends CRM_Nav_Data_EntityData_Base {

  // civi address data structures
  private $_private_before;
  private $_private_after;
  private $_organisation_before;
  private $_organisation_after;
  private $_contact_id;
  private $_location_type_private;
  private $_location_type_organization;

  private $civi_private_address;
  private $civi_organization_address;


  public function __construct($before_private, $after_private, $before_organization,
                              $after_organization, $contact_id,
                              $private_location_type, $organization_location_type) {
    $this->_private_before = $before_private;
    $this->_private_after = $after_private;
    $this->_organisation_before = $before_organization;
    $this->_organisation_after = $after_organization;
    $this->_contact_id = $contact_id;
    $this->_location_type_private = $private_location_type;
    $this->_location_type_organization = $organization_location_type;

    $this->get_civi_data();
  }

  protected function get_civi_data() {
    $result = civicrm_api3('Address', 'get', array(
      'sequential' => 1,
      'contact_id' => $this->_contact_id,
      'return' => ["location_type_id",
                   "street_address",
                   "supplemental_address_1",
                   "city",
                   "postal_code",
                   "country_id",
      ],
    ));
    if ($result['is_error']) {
      $this->log("Couldn't get civi data for Address {$this->_contact_id}. Error: {$result['error_message']}");
      // TODO: throw Exception?
    }
    foreach ($result['values'] as $civi_address) {
      if ($civi_address['location_type_id'] == $this->_location_type_private) {
        $this->civi_private_address = $civi_address;
      } else {
        $this->civi_organization_address = $civi_address;
      }
    }
  }



}
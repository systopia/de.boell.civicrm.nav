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

  public function update() {
    // handle update private address
    if (!empty($this->conflict_data['private']['updates'])) {
      $values = $this->conflict_data['private']['updates'];
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Address', $values);
    }
    // handle update organization address
    if (!empty($this->conflict_data['organization']['updates'])) {
      $values = $this->conflict_data['organization']['updates'];
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Address', $values);
    }
  }

  public function delete() {
    if (!empty($this->delete_data['private']['updates'])) {
      $values = $this->delete_data['private']['updates'];
      foreach ($values as $key => $val) {
        $values[$key] = '';
      }
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Address', $values);
    }
    // handle update organization address
    if (!empty($this->delete_data['organization']['updates'])) {
      $values = $this->delete_data['organization']['updates'];
      foreach ($values as $key => $val) {
        $values[$key] = '';
      }
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Address', $values);
    }
  }

  public function calc_differences() {
    // get changed stuff
    $this->changed_data['private'] = $this->compare_data_arrays($this->_private_before, $this->_private_after);
    $this->changed_data['organization'] = $this->compare_data_arrays($this->_organisation_before, $this->_organisation_after);
    // deleted stuff
    $this->delete_data['private'] = $this->compare_delete_data($this->_private_before, $this->_private_after);
    $this->delete_data['organization'] = $this->compare_delete_data($this->_organisation_before, $this->_organisation_after);
    // conflicting stuff
    $this->conflict_data['private'] = $this->compare_conflicting_data(
      $this->civi_private_address, $this->_private_before,
      $this->changed_data['private'], 'Address'
    );
    $this->conflict_data['organization'] = $this->compare_conflicting_data(
      $this->civi_organization_address, $this->_private_before,
      $this->changed_data['organization'], 'Address'
    );
  }

  public function create_full($contact_id, $organization_id) {
    // create company address
    if (isset($this->_organisation_after)) {
      $org_values = $this->_organisation_after;
      $org_values['contact_id'] = $organization_id;
      $org_addr_id = $this->create_entity('Address', $org_values)['id'];
    }
    // create shared company address
    if (isset($this->_private_after)) {
      $contact_values = $this->_organisation_after;
      $contact_values['contact_id'] = $contact_id;
      if (isset($org_addr_id)) {
        $contact_values['master_id'] = $org_addr_id;
      }
      $this->create_entity('Address', $contact_values);
    }

    // create private address
    if (isset($this->_private_after)) {
      $address_values = $this->_private_after;
      $address_values['contact_id'] = $contact_id;
      $this->create_entity('Address', $address_values);
    }
  }

  protected function get_civi_data() {
    if (empty($this->_contact_id)) {
      return;
    }
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

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
 * Class CRM_Nav_Data_EntityData_Address
 */
class CRM_Nav_Data_EntityData_Address  extends CRM_Nav_Data_EntityData_Base {

  // civi address data structures
  private $_private_before;
  private $_private_after;
  private $_organisation_before;
  private $_organisation_after;
  private $_location_type_private;
  private $_location_type_organization;
  private $_organization_id;

  private $civi_private_address;
  private $civi_organization_address;

  /**
   * CRM_Nav_Data_EntityData_Address constructor.
   *
   * @param $before_private
   * @param $after_private
   * @param $before_organization
   * @param $after_organization
   * @param $contact_id
   * @param $organization_id
   * @param $private_location_type
   * @param $organization_location_type
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($before_private, $after_private, $before_organization,
                              $after_organization, $contact_id, $organization_id,
                              $private_location_type, $organization_location_type) {
    $this->_private_before = $before_private;
    $this->_private_after = $after_private;
    $this->_organisation_before = $before_organization;
    $this->_organisation_after = $after_organization;
    $this->_contact_id = $contact_id;
    $this->_organization_id = $organization_id;
    $this->_location_type_private = $private_location_type;
    $this->_location_type_organization = $organization_location_type;

    $this->get_civi_data();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function update() {
    // handle update private address
    if (!empty($this->conflict_data['private']['updates'])) {
      $values = $this->conflict_data['private']['updates'];
      $values['contact_id'] = $this->_contact_id;
      $values['location_type_id'] = $this->_location_type_private;
      $this->create_entity('Address', $values);
    }
    // handle update organization address
    if (!empty($this->conflict_data['organization']['updates'])) {
      $values = $this->conflict_data['organization']['updates'];
      $values['location_type_id'] = $this->_location_type_organization;
      // set to primary
      $values['is_primary'] = '1';
      $values['contact_id'] = $this->_contact_id;
      // if we don't have an ID, the address will be newly created
      // we need the Org_id, lookup the address and add it as master_id
      if (empty($values['id'])) {
        $master_address_id = $this->get_organization_address();
        if (empty($master_address_id)) {
          $this->log("Couldn't determine Master Address ID for {$this->_organization_id} Creating Address, but wont be shared with Company");
        } else {
          $values['master_id'] = $master_address_id;
        }
      }
      $this->create_entity('Address', $values);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function apply_changes() {
    // handle update private address
    if (!empty($this->conflict_data['private']['valid_changes'])) {
      $values = $this->conflict_data['private']['valid_changes'];
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Address', $values);
    }
    // handle update organization address
    if (!empty($this->conflict_data['organization']['valid_changes'])) {
      $values = $this->conflict_data['organization']['valid_changes'];
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Address', $values);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
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

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function i3val() {
    if (!empty($this->conflict_data['private']['i3val'])) {
      $values = $this->conflict_data['private']['i3val'];
      $values['id'] = $this->_contact_id;
      $this->i3val_update($values);
    }

    if (!empty($this->conflict_data['organization']['i3val'])) {
      $values = $this->conflict_data['organization']['i3val'];
      $values['id'] = $this->_contact_id;
      $this->i3val_update($values);
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

  /**
   * Create complete new Contact for all Values from Navision
   *
   * @param $contact_id
   * @param $organization_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function create_full($contact_id, $organization_id) {
    // create company address
    if (isset($this->_organisation_after)) {
      $org_values = $this->_organisation_after;
      $org_values['contact_id'] = $organization_id;
      $org_addr_id = $this->create_entity('Address', $org_values)['id'];
    }
    // create shared company address
    if (isset($this->_private_after)) {
      $org_address_values = $this->_organisation_after;
      $org_address_values['contact_id'] = $contact_id;
      if (isset($org_addr_id)) {
        $org_address_values['master_id'] = $org_addr_id;
      }
      $org_address_values['is_primary'] = '1';
      $this->create_entity('Address', $org_address_values);
    }

    // create private address
    if (isset($this->_private_after)) {
      $priv_address_values = $this->_private_after;
      $priv_address_values['contact_id'] = $contact_id;
      $this->create_entity('Address', $priv_address_values);
    }
  }

  /**
   * @param $organization_id
   */
  public function set_organization_id($organization_id) {
    $this->_organization_id = $organization_id;
  }

  /**
   * Get Data from CiviCRM
   * @throws \CiviCRM_API3_Exception
   */
  protected function get_civi_data() {
    if (empty($this->_contact_id)) {
      return;
    }
    $values =  ['sequential' => 1,
                'contact_id' => $this->_contact_id,
                'return' => ["location_type_id",
                             "street_address",
                             "supplemental_address_1",
                             "city",
                             "postal_code",
                             "country_id",
                ],
      ];
    $result = $this->get_entity('Address', $values);
    foreach ($result['values'] as $civi_address) {
      if ($civi_address['location_type_id'] == $this->_location_type_private) {
        $this->civi_private_address = $civi_address;
      } else {
        $this->civi_organization_address = $civi_address;
      }
    }
  }

  /**
   * get addresses from $this->_organization_address, compares them to nav_after
   * and returns id from said address. If no address is found, return ''.
   * Compare is first all available data, then just street_address, city and postal_code
   * It shouldn't be possible to get an invalid address
   *    (Company should be updated first in Navision, thus address always valid)
   *
   * @return String
   * @throws \CiviCRM_API3_Exception
   */
  private function get_organization_address() {
    if (empty($this->_organization_id)) {
      return '';
    }
    $values =  ['sequential' => 1,
                'contact_id' => $this->_organization_id,
                'return' => ["location_type_id",
                             "street_address",
                             "supplemental_address_1",
                             "city",
                             "postal_code",
                             "country_id",
                ],
    ];
    $result = $this->get_entity('Address', $values);
    foreach ($result['values'] as $address) {
      if ($address == $this->_organisation_after) {
        return $address['id'];
      }
      // we check a couple of more fields to be sure (remove location_type, country_id etc
      if ($address['street_address'] == $this->_organisation_after['street_address'] &&
        $address['city'] == $this->_organisation_after['city'] &&
        $address['postal_code'] == $this->_organisation_after['postal_code']
      ) {
        return $address['id'];
      }
    }
    return '';
  }

}

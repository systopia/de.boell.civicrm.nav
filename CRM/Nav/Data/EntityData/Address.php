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
  private $_address_before;
  private $_address_after;

  private $_location_type_private;
  private $_location_type_organization;
  private $_organization_id;

  private $_is_organization;

  private $_disconnect;

  private $civi_private_address;
  private $civi_organization_address;

  /**
   * CRM_Nav_Data_EntityData_Address constructor.
   *
   * @param $before_private
   * @param $after_private
   * @param $contact_id
   * @param $organization_id
   * @param $private_location_type
   * @param $organization_location_type
   *
   * @param $is_organization
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($before_private, $after_private, $contact_id, $organization_id,
                              $private_location_type, $organization_location_type, $is_organization, $disconnect =FALSE) {
    $this->_address_before             = $before_private;
    $this->_address_after              = $after_private;
    $this->_contact_id                 = $contact_id;
    $this->_organization_id            = $organization_id;
    $this->_location_type_private      = $private_location_type;
    $this->_location_type_organization = $organization_location_type;
    $this->_is_organization            = $is_organization;

    $this->_disconnect                 = $disconnect;

    $this->get_civi_data();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function update() {
    // handle update private address
    if (!empty($this->conflict_data['updates']) && !$this->_is_organization) {

      $values = $this->conflict_data['updates'];
      $values['contact_id'] = $this->_contact_id;
      $values['location_type_id'] = $this->_location_type_private;
      $this->create_entity('Address', $values);
    }

    if (!empty($this->_organization_id)) {
      $local_business_address = $this->get_organization_address($this->_contact_id);
      $company_address = $this->get_organization_address($this->_organization_id);

      // create company address
      if (!empty($company_address) && (!$this->compare_addresses($local_business_address, $company_address))) {
        // delete old business address if available, add new linked address
        if (!empty($local_business_address['id'])) {
          $this->delete_entity('Address', $local_business_address['id']);
        }
        $company_address['contact_id'] = $this->_contact_id;
        $company_address['master_id'] = $company_address['id'];
        $company_address['is_primary'] = '1';
        unset($company_address['id']);
        $this->create_entity('Address', $company_address);
      }
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function apply_changes() {
    // handle update private address
    if (!empty($this->conflict_data['valid_changes']) && !$this->_is_organization) {
      $values = $this->conflict_data['valid_changes'];
      $values['contact_id'] = $this->_contact_id;
      $this->create_entity('Address', $values);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function delete() {
    // check if address needs to be disconnected
    $this->disconnect_from_organization();
    if ($this->check_delete_address() && isset($this->civi_private_address['id'])) {
      $this->delete_entity('Address', $this->civi_private_address['id']);
      // prevent possible update
      if ($this->conflict_data['updates']['id'] == $this->civi_private_address['id']) {
        unset($this->conflict_data['updates']);
      }
      return;
    }
    if (!empty($this->delete_data['updates']) && !$this->_is_organization) {
      $values = $this->delete_data['updates'];
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
    if (!empty($this->conflict_data['i3val']) && !$this->_is_organization) {
      $values = $this->conflict_data['i3val'];
      $values['id'] = $this->_contact_id;
      $this->i3val_update($values);
    }
  }

  public function calc_differences() {
    if (empty($this->civi_private_address)) {
      // we don't have data in civi, but this is a change set.
      // We now fill all values for changed data to update the whole Entity with after data
      $this->changed_data = $this->_address_after;
    } else {
      // get changed stuff
      $changed_data_set = $this->compare_data_arrays($this->_address_before, $this->_address_after);
      // if there are no changes, check if after and current civi values differ.
      // (Always differ in id field, but if more than one field differs, we must update whole Entity)
      $number_of_differences_civi_after = count($this->compare_data_arrays($this->_address_after, $this->civi_private_address));
      if (empty($changed_data_set) &&  $number_of_differences_civi_after > '1') {
        $this->changed_data = $this->_address_after;
      } else {
        $this->changed_data = $changed_data_set;
      }
    }
    $this->correct_civi_country_id();
    // TODO: Fix Country_id: map to option_value
    // deleted stuff
    $this->delete_data = $this->compare_delete_data($this->_address_before, $this->_address_after);
    // remove deleted stuff from updates
    $this->check_if_value_is_deleted($this->changed_data, $this->delete_data);
    // conflicting stuff
    $this->conflict_data = $this->compare_conflicting_data(
      $this->civi_private_address, $this->_address_before,
      $this->changed_data, 'Address'
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
    // create private address
    if (!empty($this->_address_after)) {
      $address_values = $this->_address_after;
      $address_values['contact_id'] = $contact_id;
      $this->create_entity('Address', $address_values);
    }

    if (empty($organization_id)) {
      return;
    }
    // get civi Company Address
    $company_address = $this->get_organization_address($organization_id);
    // create company address
    if (!empty($company_address)) {
      $company_address['contact_id'] = $contact_id;
      $company_address['master_id'] = $company_address['id'];
      unset($company_address['id']);
      $this->create_entity('Address', $company_address);
      return;
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

  private function check_delete_address() {
    // Check if $this->delete_data == $this->civi (ignore country_id and id)
    foreach ($this->civi_private_address as $key => $value) {
      if ($key == 'id' || $key == 'country_id') {
        continue;
      }
      if (!isset($this->delete_data[$key])) {
        // we have more values in civi than in delete data,
        // thus don't delete full address
        return FALSE;
      }
    }
    // all values except id and country_id are deleted --> delete whole address
    return TRUE;
  }

  private function disconnect_from_organization() {
    if (!$this->_disconnect) {
      return;
    }
    // get all addresses from contact with master_id NOT NULL (Should return just one address)
    $result = civicrm_api3('Address', 'get', array(
      'sequential' => 1,
      'contact_id' => $this->_contact_id,
      'master_id' => array('IS NOT NULL' => 1),
    ));
    if ($result['count'] != '1') {
      $this->log("Couldn't get address from contact {$this->_contact_id}.");
      return;
    }
    // delete address
    $this->delete_entity('Address', $result['id']);
  }

  private function correct_civi_country_id() {
    if (!isset($this->changed_data['country_id'])) {
      return;
    }
    $country_iso_code = $this->changed_data['country_id'];
    $result = civicrm_api3('Country', 'get', array(
      'sequential' => 1,
      'iso_code' => $country_iso_code,
    ));
    if ($result['count'] != '1' || $result['is_error'] == '1') {

      $this->log("Couldn't resolve Country ISO Code ({$country_iso_code}). Returning ISO Code - This will probably end up in i3Val then. ERROR MEssage: {$result['error_message']}");
    }
    $this->changed_data['country_id'] = $result['id'];
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
  private function get_organization_address($orgnization_id) {
    if (empty($orgnization_id)) {
      return '';
    }
    $values =  ['sequential' => 1,
                'contact_id' => $orgnization_id,
                'is_primary' => '1',
                "location_type_id" => $this->_location_type_organization,
                'return' => ["location_type_id",
                             "street_address",
                             "supplemental_address_1",
                             "city",
                             "postal_code",
                             "country_id",
                ],
    ];
    $result = $this->get_entity('Address', $values);
    if ($result['count'] == 1) {
      return $result['values']['0'];
    }
    return '';
  }

  /**
   * Compares 2 address arrays, but ignores the id field
   * @param $address1
   * @param $address2
   *
   * @return bool
   */
  private function compare_addresses($address1, $address2) {
    if (empty($address1)) {
      // we don't have values in civi
      return FALSE;
    }
    foreach ($address1 as $key => $value) {
      if ($key == 'id') {
        continue;
      }
      if (!isset($address2[$key]) || $address2[$key] != $value) {
        return FALSE;
      }
    }
    return TRUE;
  }

}

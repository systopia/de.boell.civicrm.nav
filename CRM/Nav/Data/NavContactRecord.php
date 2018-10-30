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
 * Class CRM_Nav_Data_NavContactRecord
 */
class CRM_Nav_Data_NavContactRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type                       = "civiContact";

// Data Classes for Civi Entities
  private $Contact;
  private $Email;
  private $Address;
  private $Phone;
  private $Website;


  private   $location_type_private;
  private   $location_type_organization;
  private   $website_type_id;
  private $org_name_1;
  private $org_name_2;

  private   $matcher;

  private $contactType;

  /**
   * CRM_Nav_Data_NavContactRecord constructor.
   *
   * @param      $nav_data_after
   * @param null $nav_data_before
   *
   * @throws \Exception
   */
  public function __construct($nav_data_after, $nav_data_before = NULL) {
    $this->org_name_1 = CRM_Nav_Config::get('org_name_1');
    $this->org_name_2 = CRM_Nav_Config::get('org_name_1');
    $this->location_type_private = CRM_Nav_Config::get('location_type_private');
    $this->location_type_organization = CRM_Nav_Config::get('location_type_organization');
    $this->website_type_id = CRM_Nav_Config::get('website_type_id');

    parent::__construct($nav_data_after, $nav_data_before);
    $this->matcher = new CRM_Nav_Data_NavContactMatcherCivi($this->navision_custom_field, $this->org_name_1, $this->org_name_2);
  }

  /**
   * @throws \Exception
   */
  protected function convert_to_civi_data() {
    // set location data
    $this->set_location_type_ids();
    // contact_data
    $this->convert_civi_contact_data();
    // convert Entities
    $this->convert_civi_addresses();
    $this->convert_civi_email_data();
    $this->convert_civi_website_data();
    $this->convert_civi_phone_data();

    $this->clean_civi_data('before');
    $this->clean_civi_data('after');
  }

  public function create_full_contact() {
    $this->Contact->create_full();
    $contact_id = $this->Contact->get_contact_id();
    $org_id = $this->Contact->get_org_id();
    $this->Address->create_full($contact_id, $org_id);
    $this->Phone->create_full($contact_id);
    $this->Email->create_full($contact_id);
    $this->Website->create_full($contact_id);
  }

  public function get_or_create_contact() {
    return $this->Contact->get_or_create_contact();
  }

  public function calc_differences() {
    $this->Contact->calc_differences();
    $this->Address->calc_differences();
    $this->Phone->calc_differences();
    $this->Email->calc_differences();
    $this->Website->calc_differences();
  }

  public function update() {
    $this->Contact->update();
    $this->Address->update();
    $this->Phone->update();
    $this->Email->update();
    $this->Website->update();
  }

  public function delete() {
    $this->Contact->delete();
    $this->Address->delete();
    $this->Phone->delete();
    $this->Email->delete();
    $this->Website->delete();
  }

  /**
   * @throws \Exception
   */
  private function convert_civi_contact_data() {

    $nav_data_after              = $this->get_nav_after_data();
    $nav_data_before             = $this->get_nav_before_data();
    // individual
    $civi_data_before_individual = $this->create_civi_contact_values($nav_data_before);
    $civi_data_after_individual  = $this->create_civi_contact_values($nav_data_after);

    // company
    $civi_data_before_individual_company = $this->create_civi_contact_data_organization($nav_data_before);
    $civi_data_after_individual_company  = $this->create_civi_contact_data_organization($nav_data_after);

    $lookup_data = $this->get_contact_lookup_details();
    $this->Contact = new CRM_Nav_Data_EntityData_Contact(
      $civi_data_before_individual,
      $civi_data_after_individual,
      $civi_data_before_individual_company,
      $civi_data_after_individual_company,
      $this->get_individual_navision_id(),
      $lookup_data,
      $this
    );

  }

  /*
 * check if organization data is set
 * if so - add 'organization_address' to $civi_extra_data
 * and fill in data from compare
 */
  private function convert_civi_addresses() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    // Private Address
    $private_before = $this->create_civi_address_values_private($nav_data_before);
    $private_after  = $this->create_civi_address_values_private($nav_data_after);
    // organizationAddress
    $organization_before  = $this->create_civi_address_values_organization($nav_data_before);
    $organization_after  = $this->create_civi_address_values_organization($nav_data_after);

    $contact_id = $this->Contact->get_contact_id();

    $this->Address = new CRM_Nav_Data_EntityData_Address(
      $private_before,
      $private_after,
      $organization_before,
      $organization_after,
      $contact_id,
      $this->location_type_private,
      $this->location_type_organization
    );
  }

  /**
   * convert_civi_phone_data
   */
  private function convert_civi_phone_data() {
    $nav_data_after                  = $this->get_nav_after_data();
    $nav_data_before                 = $this->get_nav_before_data();

    $phone_org   = [];
    $mobile_org  = [];
    $fax_org     = [];
    $fax_priv    = [];
    $mobile_priv = [];
    $phone_priv  = [];
    if (isset($nav_data_after['Phone_No'])) {
      $phone_org['after'] = $this->create_civi_phone_values($this->location_type_organization, "Phone", 'Phone_No', $nav_data_after);
    }
    if (isset($nav_data_before['Phone_No'])) {
      $phone_org['before'] = $this->create_civi_phone_values($this->location_type_organization, "Phone", 'Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Mobile_Phone_No'])) {
      $mobile_org['after'] = $this->create_civi_phone_values($this->location_type_organization, "Mobile", 'Mobile_Phone_No', $nav_data_after);
    }
    if (isset($nav_data_before['Mobile_Phone_No'])) {
      $mobile_org['before'] = $this->create_civi_phone_values($this->location_type_organization, "Mobile", 'Mobile_Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Fax_No'])) {
      $fax_org['after'] = $this->create_civi_phone_values($this->location_type_organization, "Fax", 'Fax_No', $nav_data_after);
    }
    if (isset($nav_data_before['Fax_No'])) {
      $fax_org['before'] = $this->create_civi_phone_values($this->location_type_organization, "Fax", 'Fax_No', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Faxnr'])) {
      $fax_priv['after'] = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_after);
    }
    if (isset($nav_data_before['Private_Faxnr'])) {
      $fax_priv['before'] = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_before);
    }
    if (isset($nav_data_after['Privat_Mobile_Phone_No'])) {
      $mobile_priv['after'] = $this->create_civi_phone_values($this->location_type_private, "Mobile", 'Privat_Mobile_Phone_No', $nav_data_after);
    }
    if (isset($nav_data_before['Privat_Mobile_Phone_No'])) {
      $mobile_priv['before'] = $this->create_civi_phone_values($this->location_type_private, "Mobile", 'Privat_Mobile_Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Telefonnr'])) {
      $phone_priv['after']  = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_after);
    }
    if (isset($nav_data_before['Private_Telefonnr'])) {
      $phone_priv['before'] = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_before);
    }
    $this->Phone = new CRM_Nav_Data_EntityData_Phone(
      $phone_org,
      $mobile_org,
      $fax_org,
      $phone_priv,
      $mobile_priv,
      $fax_priv,
      $this->location_type_private,
      $this->location_type_organization,
      $this->Contact->get_contact_id()
    );
  }

  /**
   * convert_civi_phone_data
   */
  private function convert_civi_website_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    // Homepage
    $website_before = [];
    $website_after = [];
    if (isset($nav_data_after['Home_Page'])) {
      $website_after = [
        'url'             => $this->get_nav_value_if_exist($nav_data_after, 'Home_Page'),
        'website_type_id' => $this->website_type_id,
      ];
    }
    if (isset($nav_data_before['Home_Page'])) {
      $website_before = [
        'url'             => $this->get_nav_value_if_exist($nav_data_before, 'Home_Page'),
        'website_type_id' => $this->website_type_id,
      ];
    }

    $this->Website = new CRM_Nav_Data_EntityData_Website(
      $website_before,
      $website_after,
      $this->Contact->get_contact_id()
    );
  }

  /**
   * convert_civi_phone_data
   */
  private function convert_civi_email_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();
    //Email
    // FixMe: Primary email is in Civi_person_data()
    $email_org_1 = [];
    $email_priv = [];
    $email_priv_2 = [];
    if (isset($nav_data_after['E_Mail'])) {
      $email_org_1['after']  = $this->create_civi_mail_values($this->location_type_organization, 'E_Mail', $nav_data_after);
    }
    if (isset($nav_data_before['E_Mail'])) {
      $email_org_1['before'] = $this->create_civi_mail_values($this->location_type_organization, 'E_Mail', $nav_data_before);
    }
    if (isset($nav_data_after['E_Mail_2'])) {
      $email_priv_2['after'] = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_after);
    }
    if (isset($nav_data_before['E_Mail_2'])) {
      $email_priv_2['before'] = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_before);
    }
    if (isset($nav_data_after['Private_E_Mail'])) {
      $email_priv['after'] = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_after);
    }
    if (isset($nav_data_before['Private_E_Mail'])) {
      $email_priv['before'] = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_before);
    }
    $this->Email = new CRM_Nav_Data_EntityData_Email(
      $email_org_1,
      $email_priv,
      $email_priv_2,
      $this->location_type_private,
      $this->location_type_organization,
      $this->Contact->get_contact_id()
    );
  }

/////////////////////////
  /// Getter
/////////////////////////
  /**
   * @param string $contact_type
   *
   * @return mixed
   */
  public function get_contact_details() {
    foreach ($this->civi_data_after['Contact'] as $contact) {
      if ($contact['contact_type'] == $this->contactType) {
        return $contact;
      }
    }
  }

  /**
   * @return mixed
   * @throws \Exception
   */
  public function get_civi_individual_address() {
    switch ($this->contactType) {
      case 'Individual':
        return $this->civi_data_after['Address']['individual'];
      case 'Organization':
        return $this->civi_data_after['Address']['organization'];
      default:
        throw new Exception("Invalid contactType {$this->contactType}");
    }
  }

  /**
   * @return mixed
   */
  public function get_civi_phones() {
    return $this->civi_data_after['Phone'];
  }

  /**
   * @return mixed
   */
  public function get_civi_emails() {
    return $this->civi_data_after['Email'];
  }

  /**
   * @return mixed
   */
  public function get_civi_website() {
    return $this->civi_data_after['Website'];
  }

  /**
   * overwrite possible locationtype IDs in case of Type = Company
   */
  private function set_location_type_ids() {
    $nav_data = $this->get_nav_after_data();
    if ($nav_data['Type'] == 'Company') {
      // overwrite private location type id, this is 'geschaeftlich' now
      $this->location_type_private = $this->location_type_organization;
    }
  }

  /**
   * @param string $type
   *
   * @return array
   */
  public function get_company_data($type = 'after') {
    switch ($type) {
      case 'before':
        $civi_data = &$this->civi_data_before;
        $nav_data = $this->get_nav_before_data();
        break;
      case 'after':
        $civi_data = &$this->civi_data_after;
        $nav_data = $this->get_nav_after_data();
        break;
      default:
        return array();
    }
    if ($nav_data['Type'] == 'Company' || $nav_data['Company_No'] == $nav_data['No']) {
      // nothing to do here, as a company we don't have shared addresses
      // we also don't share addresses if the No & Company_No don't differ
      //    (no linked company that way)
      return array();
    }
    foreach ($civi_data['Contact'] as $contact) {
      if ($contact['contact_type'] == "Organization") {
        $result['Contact'] = $contact;
      }
    }
    $result['Address'] = $civi_data['Address']['organization'];
    $result['Org_nav_id'] = $nav_data['Company_No'];

    return $result;
  }

  /**
   * @return bool
   */
  public function company_changed() {
    $nav_data_before = $this->get_nav_before_data();
    $nav_data_after  = $this->get_nav_after_data();
    return $nav_data_before['Company_No'] !== $nav_data_after['Company_No'];
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function get_delete_entities() {
    $entities = [];
    foreach ($this->delete_data as $key => $value) {
      $mapping = $this->matcher->get_entity($key);
      $tmp_value = reset($mapping);
      if (isset($tmp_value['location_type_id'])) {
        if ($tmp_value['location_type_id'] == 'private') {
          $tmp_value['location_type_id'] = $this->location_type_private;
        } else {
          $tmp_value['location_type_id'] = $this->location_type_organization;
        }
      }
      $tmp_value[$this->matcher->get_civi_values($key)] = $value;
      $entities[key($mapping)][] = $tmp_value;
    }
    $this->get_delete_lookup_values($entities);
    return $entities;
  }

  /**
   * Get whole entities from before and add them to $entities array
   * @param $entities
   */
  private function get_delete_lookup_values(&$entities) {
    $civi_before_entities = [];
    foreach ($entities as $entity) {
      $civi_entity = key($entities);
      foreach ($entity as $entry => $value) {
        $result = $this->find_civi_entity($civi_entity, $value);
        $civi_before_entities[$civi_entity][$entry] = $result;
      }
    }
    $entities['lookup_values'] = $civi_before_entities;
  }

  private function find_civi_entity($entity, $del_values) {
    foreach ($this->civi_data_before[$entity] as $e => $val) {
      $match = FALSE;
      foreach ($del_values as $del_key => $del_value) {
        if (isset($val[$del_key]) && $val[$del_key] == $del_value) {
          $match = TRUE;
        } else {
          $match = FALSE;
        }
      }
      if ($match) {
        return $val;
      }
    }
  }



  /**
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_address_values_private($nav_data) {
    return [
      'street_address'         => $this->get_nav_value_if_exist($nav_data, 'Address'),
      'supplemental_address_1' => $this->get_nav_value_if_exist($nav_data, 'Address_2'),
      'postal_code'            => $this->get_nav_value_if_exist($nav_data, 'Post_Code'),
      'city'                   => $this->get_nav_value_if_exist($nav_data, 'City'),
      'country_id'             => $this->get_nav_value_if_exist($nav_data, 'Country_Region_Code'),
      'location_type_id'       => $this->location_type_private,
    ];
  }

  /**
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_address_values_organization($nav_data) {
    return [
      'street_address'         => $this->get_nav_value_if_exist($nav_data, 'Company_Adress'),
      'supplemental_address_1' => $this->get_nav_value_if_exist($nav_data, 'Company_Adress_2'),
      'postal_code'            => $this->get_nav_value_if_exist($nav_data, 'Company_Post_Code'),
      'city'                   => $this->get_nav_value_if_exist($nav_data, 'Company_City'),
      'country_id'             => $this->get_nav_value_if_exist($nav_data, 'Company_Country_Region_Code'),
      'location_type_id'       => $this->location_type_organization,
    ];
  }

  /**
   * @param $location_type
   * @param $phone_type
   * @param $nav_index
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_phone_values($location_type, $phone_type, $nav_index, $nav_data) {
    return [
      'phone'            => $this->get_nav_value_if_exist($nav_data, $nav_index),
      'location_type_id' => $location_type,
      'phone_type_id'    => $phone_type,
    ];
  }

  /**
   * @param $location_type
   * @param $nav_index
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_mail_values($location_type, $nav_index, $nav_data) {
    return [
      'email'            => $this->get_nav_value_if_exist($nav_data, $nav_index),
      'location_type_id' => $location_type,
    ];
  }


  /**
   *
   * @param $type
   * // TODO: DEPRECIATED
   * @throws \Exception
   */
  private function clean_civi_data($type) {
    switch ($type) {
      case 'before':
        if (empty($this->civi_data_before)) {
          return;
        }
        $civi_data = &$this->civi_data_before;
        break;
      case 'after':
        $civi_data = &$this->civi_data_after;
        break;
      default:
        throw new Exception('Invalid Type {$type} in clean_civi_data');
    }
    foreach ($civi_data as $entity_name => &$civi_entity) {
      foreach ($civi_entity as $key => &$values) {
        foreach ($values as $name => &$val) {
          if ($val == "") {
            if ($entity_name == 'Phone' || $entity_name == 'Email') {
              // unset parent
              unset($civi_entity[$key]);
              break;
            }
            else {
              // unset child
              unset($values[$name]);
            }
          }
        }
      }
    }
  }

  /**
   * @param $type
   *
   * @return string
   * @throws \Exception
   */
  private function get_contact_type($type) {
    switch ($type) {
      case 'Person':
        return "Individual";
      case 'Company':
        return "Organization";
      case "":
        return "";
      default:
        throw new Exception("Invalid Field for Contact_type. Must be either Person or Company");
    }
  }

  /**
   * @param $nav_data
   *
   * @return array
   * @throws \Exception
   */
  private function create_civi_contact_values($nav_data) {
    $contact_type = $this->get_nav_value_if_exist($nav_data, 'Type');
//    if (empty($this->contactType)) {}
    $this->contactType = $this->get_contact_type($contact_type);
    if (empty($contact_type)) {
      return [];
    }
    switch ($contact_type) {
      case 'Company':
        // nothing to do here. No Person data needs to be created
        return;
      case 'Person':
      return [
        // TODO: Iterate over fields from NavContactMatcher and make this more generic!
        'contact_type' => "Individual",
        'first_name'   => $this->get_nav_value_if_exist($nav_data, 'First_Name'),
        'middle_name'  => $this->get_nav_value_if_exist($nav_data, 'Middle_Name'),
        'last_name'    => $this->get_nav_value_if_exist($nav_data, 'Surname'),
        'birth_date'   => $this->get_nav_value_if_exist($nav_data, 'Geburtsdatum'),
        $this->navision_custom_field   => $this->get_nav_value_if_exist($nav_data, 'No'),        // NavisionID
//        'email'        => $this->get_nav_value_if_exist($nav_data, 'E_mail'),
        'formal_title' => $this->get_nav_value_if_exist($nav_data, 'Job_Title'),
        'job_title'    => $this->get_nav_value_if_exist($nav_data, 'Funktion'),
        'contact_type' => $this->get_contact_type($this->get_nav_value_if_exist($nav_data, 'Type')),
        'prefix_id'    => $this->get_nav_value_if_exist($nav_data, 'Salutation_Code'),
      ];
      default:
        throw new Exception("Invalid Contact Type {$contact_type}. Couldn't convert Navision Data to CiviCRM data.");
    }

  }






  /**
   * @param $fields
   *
   * @return bool
   */
  private function value_changed($fields) {
    foreach ($fields as $f) {
      if (array_key_exists($f, $this->changed_data) && $this->changed_data[$f] != "") {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_contact_data_organization($nav_data) {
    return [
      // NavisionID
      'contact_type' => "Organization",
      $this->navision_custom_field   => $this->get_nav_value_if_exist($nav_data, 'Company_No'),
      $this->org_name_1   => $this->get_nav_value_if_exist($nav_data, 'Company_Name'),
      $this->org_name_2   => $this->get_nav_value_if_exist($nav_data, 'Company_Name_2'),
      'organization_name' => ($this->get_nav_value_if_exist($nav_data, 'Company_Name') ." " . $this->get_nav_value_if_exist($nav_data, 'Company_Name_2')),
    ];
  }


  /**
   * @return mixed
   */
  private function get_contact_lookup_details() {
    $result['Emails'] = $this->get_contact_email();
    $nav_data = $this->get_nav_before_data();
    $first_name = $this->get_nav_value_if_exist($nav_data, 'First_Name');
    if (isset($first_name)) {
      $result['Contact']['first_name'] = $first_name;
    }
    $last_name = $this->get_nav_value_if_exist($nav_data, 'Surname');
    if (isset($last_name)) {
      $result['Contact']['last_name'] = $last_name;
    }
    return $result;
  }

  /**
   * @return array
   */
  private function get_contact_email() {
    $nav_data = $this->get_nav_before_data();
    $result= [];
    if (isset($nav_data['E_mail'])) {
      $result[] = $nav_data['E_mail'];
    }
    if (isset($nav_data['E_mail_2'])) {
      $result[] = $nav_data['E_mail_2'];
    }
    if (isset($nav_data['Private_E_Mail'])) {
      $result[] = $nav_data['Private_E_Mail'];
    }
    return $result;
  }

  /**
   * @param      $entity
   * @param null $filter
   *
   * @return array|void
   */
  public function get_i3val_values($entity, $filter = NULL) {
    // if we don't have a filter array, or we are looking for Contact Entity,
    // return all after values.
    if (empty($filter) || $entity == 'Contact') {
      return;
    }
    $result_values = array();
    foreach ($filter as $filter_value) {
      foreach ($this->civi_data_before[$entity] as $entity_key => $entity_value) {
        if ($filter_value == $entity_value) {
          $result_values[] = $this->civi_data_after[$entity][$entity_key];
        }
      }
    }
    return $result_values;
  }

  /**
   * @param $type
   *
   * @return array
   * @throws \Exception
   */
  public function get_changed_contact_values($type) {
    $result         = [];
    $contact_fields = $this->matcher->get_contact_fields('Individual');
    foreach ($contact_fields as $field) {
      if (array_key_exists($field, $this->changed_data) && $this->changed_data[$field] != "") {
        switch ($type) {
          case 'after':
            $result['Contact'][$this->matcher->get_civi_values($field)] = $this->changed_data[$field];
            break;
          case 'before':
            $nav_data_before                                            = $this->get_nav_before_data();
            $result['Contact'][$this->matcher->get_civi_values($field)] = $nav_data_before[$field];
            break;
          default:
            throw new Exception("Invalid type '{$type}' in get_contact_values");
        }
      }
    }
    return $result;
  }

  /**
   * @param $type
   *
   * @return array
   * @throws \Exception
   */
  public function get_changed_Address_values($type) {
    $result                 = [];
    $private_address_fields = $this->matcher->get_address_fields('private');
    if ($this->value_changed($private_address_fields)) {
      switch ($type) {
        case 'after':
          $result['Address'][] = $this->civi_data_after['Address']['individual'];
          break;
        case 'before':
          $result['Address'][] = $this->civi_data_before['Address']['individual'];
          break;
        default:
          throw new Exception("Invalid type '{$type}' in get_changed_Address_values");
      }
    }
    return $result;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function get_unchanged_Address_values() {
    $result                 = [];
    $private_address_fields = $this->matcher->get_address_fields('private');
    if (!$this->value_changed($private_address_fields)) {
      $result['Address']['after'][] = $this->civi_data_after['Address']['individual'];
      $result['Address']['before'][] = $this->civi_data_before['Address']['individual'];
    }
    return $result;
  }

  /**
   * @param $type
   *
   * @return array
   * @throws \Exception
   */
  public function get_changed_Phone_values($type) {
    $result = [];
    // Private Phone
    $private_phone_fields = $this->matcher->get_phone_fields('private');
    if ($this->value_changed($private_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_private, "Phone", $type);
    }
    // private Fax
    $private_fax_fields = $this->matcher->get_fax_fields('private');
    if ($this->value_changed($private_fax_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_private, "Fax", $type);
    }
    // private mobile phone
    $org_phone_fields = $this->matcher->get_phone_fields('mobile_private');
    if ($this->value_changed($org_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_private, "Mobile", $type);
    }
    // organization Phone
    $org_phone_fields = $this->matcher->get_phone_fields('organization');
    if ($this->value_changed($org_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organization, "Phone", $type);
    }
    // organization Mobile
    $org_phone_fields = $this->matcher->get_phone_fields('mobile');
    if ($this->value_changed($org_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organization, "Mobile", $type);
    }
    // organization Phone
    $org_fax_fields = $this->matcher->get_fax_fields('organization');
    if ($this->value_changed($org_fax_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organization, "Fax", $type);
    }
    return $result;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function get_unchanged_Phone_values() {
    $result = [];
    // Private Phone
    $private_phone_fields = $this->matcher->get_phone_fields('private');
    if (!$this->value_changed($private_phone_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_private, "Phone", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_private, "Phone", 'after');
    }
    // private Fax
    $private_fax_fields = $this->matcher->get_fax_fields('private');
    if (!$this->value_changed($private_fax_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_private, "Fax", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_private, "Fax", 'after');
    }
    // private mobile phone
    $org_phone_fields = $this->matcher->get_phone_fields('mobile_private');
    if (!$this->value_changed($org_phone_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_private, "Mobile", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_private, "Mobile", 'after');

    }
    // organization Phone
    $org_phone_fields = $this->matcher->get_phone_fields('organization');
    if (!$this->value_changed($org_phone_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_organization, "Phone", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_organization, "Phone", 'after');
    }
    // organization Mobile
    $org_phone_fields = $this->matcher->get_phone_fields('mobile');
    if (!$this->value_changed($org_phone_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_organization, "Mobile", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_organization, "Mobile", 'after');
    }
    // organization Phone
    $org_fax_fields = $this->matcher->get_fax_fields('organization');
    if (!$this->value_changed($org_fax_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_organization, "Fax", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_organization, "Fax", 'after');
    }
    return $result;
  }

  /**
   * @param $type
   *
   * @return array
   * @throws \Exception
   */
  public function get_changed_Email_values($type) {
    $result       = [];
    $email_fields = $this->matcher->get_email_fields('organization');
    foreach ($email_fields as $email) {
      if (array_key_exists($email, $this->changed_data)) {
        $result['Email'][] = $this->get_email_value($this->location_type_organization, $email, $type);
      }
    }
    $email_fields = $this->matcher->get_email_fields('private');
    foreach ($email_fields as $email) {
      if (array_key_exists($email, $this->changed_data)) {
        $result['Email'][] = $this->get_email_value($this->location_type_private, $email, $type);
      }
    }
    return $result;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function get_unchanged_Email_values() {
    $result       = [];
    $email_fields = $this->matcher->get_email_fields('organization');
    foreach ($email_fields as $email) {
      if (!array_key_exists($email, $this->changed_data)) {
        $result['Email']['before'][] = $this->get_email_value($this->location_type_organization, $email, 'before');
        $result['Email']['after'][] = $this->get_email_value($this->location_type_organization, $email, 'after');
      }
    }
    $email_fields = $this->matcher->get_email_fields('private');
    foreach ($email_fields as $email) {
      if (!array_key_exists($email, $this->changed_data)) {
        $result['Email']['before'][] = $this->get_email_value($this->location_type_private, $email, 'before');
        $result['Email']['after'][] = $this->get_email_value($this->location_type_private, $email, 'after');
      }
    }
    return $result;
  }

  /**
   * Check if Website is available in changed data, otherwise take data from nav_after_data
   * @param $type
   *
   * @throws \Exception
   */
  public function get_unchanged_Website_values($type = 'after') {
    return $this->get_changed_Website_values($type);
  }

  /**
   * Just return website value, always only one website
   * @param $type
   *
   * @throws \Exception
   */
  public function get_changed_Website_values($type) {
    $result         = [];
    switch ($type) {
      case 'after':
        $result['Website'] = $this->civi_data_after['Website'];
        return $result;
      case 'before':
        $result['Website'] = $this->civi_data_before['Website'];
        return $result;
      default:
        throw new Exception("Invalid Type '{$type}' in get_changed_Website_values");
    }
  }

  /**
   * @param $location_type
   * @param $email_key
   * @param $type
   *
   * @return string
   * @throws \Exception
   */
  private function get_email_value($location_type, $email_key, $type) {
    switch ($type) {
      case 'after':
        $nav_data_after = $this->get_nav_after_data();
        foreach ($this->civi_data_after['Email'] as $email_data) {
          if ($email_data['location_type_id'] == $location_type && $email_data['email'] == $nav_data_after[$email_key]) {
            return $email_data;
          }
        }
        return "";
      case 'before':
        $nav_data_before = $this->get_nav_before_data();
        foreach ($this->civi_data_before['Email'] as $email_data) {
          if ($email_data['location_type_id'] == $location_type && $email_data['email'] == $nav_data_before[$email_key]) {
            return $email_data;
          }
        }
        return "";
      default:
        throw new Exception("Invalid Type '{$type}' in get_email_value");
    }
  }

  /**
   * @param $location_type
   * @param $phone_type
   * @param $type
   *
   * @return string
   * @throws \Exception
   */
  private function get_phone_values($location_type, $phone_type, $type) {
    switch ($type) {
      case 'after':
        foreach ($this->civi_data_after['Phone'] as $phone_data) {
          if ($phone_data['location_type_id'] == $location_type && $phone_data['phone_type_id'] == $phone_type) {
            return $phone_data;
          }
        }
        return "";
      case 'before':
        foreach ($this->civi_data_before['Phone'] as $phone_data) {
          if ($phone_data['location_type_id'] == $location_type && $phone_data['phone_type_id'] == $phone_type) {
            return $phone_data;
          }
        }
        return "";
      default:
        throw new Exception("Invalid Type '{$type}' in get_phone_values");
    }
  }
}
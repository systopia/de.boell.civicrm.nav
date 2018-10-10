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

  private   $location_type_private;
  private   $location_type_organisation;
  private $org_name_1;
  private $org_name_2;

  private   $matcher;

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
    $this->location_type_organisation = CRM_Nav_Config::get('location_type_organisation');

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
    $this->convert_civi_person_data();
    // convert addresses
    $this->convert_civi_addresses();
    // convert company extra info (Nav Id, Name)
    $this->convert_civi_organisation_data();
    // Phone/Fax/Websites
    $this->convert_civi_communication_data();

    $this->clean_civi_data('before');
    $this->clean_civi_data('after');
  }

  /**
   * @param string $contact_type
   *
   * @return mixed
   */
  public function get_contact_details($contact_type = 'Individual') {
    foreach ($this->civi_data_after['Contact'] as $contact) {
      if ($contact['contact_type'] == $contact_type) {
        return $contact;
      }
    }
  }

  /**
   * @return mixed
   */
  public function get_civi_individual_address() {
    return $this->civi_data_after['Address']['individual'];
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
      $this->location_type_private = $this->location_type_organisation;
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
    if ($nav_data['Type'] == 'Company' || $nav_data['No'] == $nav_data['Company_No']) {
      // as a company we don't have shared addresses
      // We only share addresses if No is different from Company_No (see #7687)
      return [];
    }
    foreach ($civi_data['Contact'] as $contact) {
      if ($contact['contact_type'] == "Organization") {
        $result['Contact'] = $contact;
      }
    }
    $result['Address'] = $civi_data['Address']['organisation'];
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

  /*
   * check if organisation data is set
   * if so - add 'organisation_address' to $civi_extra_data
   * and fill in data from compare
   */
  private function convert_civi_addresses() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    // Private Address
    $this->civi_data_after['Address']['individual']  = $this->create_civi_address_values_private($nav_data_after);
    $this->civi_data_before['Address']['individual'] = $this->create_civi_address_values_private($nav_data_before);
    // organisationAddress
    $this->civi_data_after['Address']['organisation']  = $this->create_civi_address_values_organisation($nav_data_after);
    $this->civi_data_before['Address']['organisation'] = $this->create_civi_address_values_organisation($nav_data_before);
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
  private function create_civi_address_values_organisation($nav_data) {
    return [
      'street_address'         => $this->get_nav_value_if_exist($nav_data, 'Company_Adress'),
      'supplemental_address_1' => $this->get_nav_value_if_exist($nav_data, 'Company_Adress_2'),
      'postal_code'            => $this->get_nav_value_if_exist($nav_data, 'Company_Post_Code'),
      'city'                   => $this->get_nav_value_if_exist($nav_data, 'Company_City'),
      'country_id'             => $this->get_nav_value_if_exist($nav_data, 'Company_Country_Region_Code'),
      'location_type_id'       => $this->location_type_organisation,
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
   * convert_civi_communication_data
   */
  private function convert_civi_communication_data() {
    $nav_data_after                  = $this->get_nav_after_data();
    $nav_data_before                 = $this->get_nav_before_data();
    $this->civi_data_after['Phone']  = [];
    $this->civi_data_before['Phone'] = [];

    if (isset($nav_data_after['Phone_No'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_organisation, "Phone", 'Phone_No', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_organisation, "Phone", 'Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Mobile_Phone_No'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_organisation, "Mobile", 'Mobile_Phone_No', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_organisation, "Mobile", 'Mobile_Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Fax_No'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_organisation, "Fax", 'Fax_No', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_organisation, "Fax", 'Fax_No', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Faxnr'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_before);
    }
    if (isset($nav_data_after['Privat_Mobile_Phone_No'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_private, "Mobile", 'Privat_Mobile_Phone_No', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_private, "Mobile", 'Privat_Mobile_Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Telefonnr'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_before);
    }
    // Homepage
    if (isset($nav_data_after['Home_Page'])) {
      $this->civi_data_after['Website'][] = [
        'url'             => $this->get_nav_value_if_exist($nav_data_after, 'Home_Page'),
        'website_type_id' => $this->location_type_private,
      ];
      $this->civi_data_before['Website'][] = [
        'url'             => $this->get_nav_value_if_exist($nav_data_before, 'Home_Page'),
        'website_type_id' => $this->location_type_private,
      ];
    }
    //Email
    // FixMe: Primary email is in Civi_person_data()
    if (isset($nav_data_after['E_Mail'])) {
      $this->civi_data_after['Email'][]  = $this->create_civi_mail_values($this->location_type_organisation, 'E_Mail', $nav_data_after);
      $this->civi_data_before['Email'][] = $this->create_civi_mail_values($this->location_type_organisation, 'E_Mail', $nav_data_before);
    }
    if (isset($nav_data_after['E_Mail_2'])) {
      $this->civi_data_after['Email'][]  = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_after);
      $this->civi_data_before['Email'][] = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_before);
    }
    if (isset($nav_data_after['Private_E_Mail'])) {
      $this->civi_data_after['Email'][]  = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_after);
      $this->civi_data_before['Email'][] = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_before);
    }
  }

  /**
   *
   * @param $type
   *
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
   * @param $nav_data
   *
   * @return array|void
   * @throws \Exception
   */
  private function create_civi_contact_values($nav_data) {
    $contact_type = $this->get_nav_value_if_exist($nav_data, 'Type');
    switch ($contact_type) {
      case 'Company':
        // nothing to do here. No Person data needs to be created
        return;
      case 'Person':
      return [
        'contact_type' => "Individual",
        'first_name'   => $this->get_nav_value_if_exist($nav_data, 'First_Name'),
        'middle_name'  => $this->get_nav_value_if_exist($nav_data, 'Middle_Name'),
        'last_name'    => $this->get_nav_value_if_exist($nav_data, 'Surname'),
        'birth_date'   => $this->get_nav_value_if_exist($nav_data, 'Geburtsdatum'),
        // NavisionID
        $this->navision_custom_field => $this->get_nav_value_if_exist($nav_data, 'No'),
        'email'                      => $this->get_nav_value_if_exist($nav_data, 'E_mail'),
        'formal_title'               => $this->get_nav_value_if_exist($nav_data, 'Job_Title'),
        'contact_type'               => $this->get_contact_type($this->get_nav_value_if_exist($nav_data, 'Type')),
        'prefix_id'                  => $this->get_nav_value_if_exist($nav_data, 'Salutation_Code'),
        'job_title'                  => $this->get_nav_value_if_exist($nav_data, 'Funktion'),
      ];
      default:
        throw new Exception("Invalid Contact Type {$contact_type}. Couldn't convert Navision Data to CiviCRM data.");
    }

  }

  /**
   * @throws \Exception
   */
  private function convert_civi_person_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    $this->civi_data_after['Contact'][]  = $this->create_civi_contact_values($nav_data_after);
    $this->civi_data_before['Contact'][] = $this->create_civi_contact_values($nav_data_before);
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
        return "Organisation";
      case "":
        return "";
      default:
        throw new Exception("Invalid Field for Contact_type. Must be either Person or Company");
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
  private function create_civi_contact_data_organisation($nav_data) {
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
   * convert_civi_organisation_data
   */
  private function convert_civi_organisation_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    $this->civi_data_after['Contact'][]  = $this->create_civi_contact_data_organisation($nav_data_after);
    $this->civi_data_before['Contact'][] = $this->create_civi_contact_data_organisation($nav_data_before);
  }

  /**
   * @return mixed
   */
  public function get_contact_lookup_details() {
    $result['Emails'] = $this->get_contact_email();
    foreach ($this->civi_data_before['Contact'] as $contact) {
      if ($contact['contact_type'] == "Individual") {
        $result['Contact'] = array (
          'first_name' => $contact['first_name'],
          'last_name'  => $contact['last_name'],
        );
      }
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
    // organisation Phone
    $org_phone_fields = $this->matcher->get_phone_fields('organisation');
    if ($this->value_changed($org_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organisation, "Phone", $type);
    }
    // organisation Mobile
    $org_phone_fields = $this->matcher->get_phone_fields('mobile');
    if ($this->value_changed($org_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organisation, "Mobile", $type);
    }
    // organisation Phone
    $org_fax_fields = $this->matcher->get_fax_fields('organisation');
    if ($this->value_changed($org_fax_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organisation, "Fax", $type);
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
    // organisation Phone
    $org_phone_fields = $this->matcher->get_phone_fields('organisation');
    if (!$this->value_changed($org_phone_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_organisation, "Phone", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_organisation, "Phone", 'after');
    }
    // organisation Mobile
    $org_phone_fields = $this->matcher->get_phone_fields('mobile');
    if (!$this->value_changed($org_phone_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_organisation, "Mobile", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_organisation, "Mobile", 'after');
    }
    // organisation Phone
    $org_fax_fields = $this->matcher->get_fax_fields('organisation');
    if (!$this->value_changed($org_fax_fields)) {
      $result['Phone']['before'][] = $this->get_phone_values($this->location_type_organisation, "Fax", 'before');
      $result['Phone']['after'][] = $this->get_phone_values($this->location_type_organisation, "Fax", 'after');
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
    $email_fields = $this->matcher->get_email_fields('organisation');
    foreach ($email_fields as $email) {
      if (array_key_exists($email, $this->changed_data)) {
        $result['Email'][] = $this->get_email_value($this->location_type_organisation, $email, $type);
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
    $email_fields = $this->matcher->get_email_fields('organisation');
    foreach ($email_fields as $email) {
      if (!array_key_exists($email, $this->changed_data)) {
        $result['Email']['before'][] = $this->get_email_value($this->location_type_organisation, $email, 'before');
        $result['Email']['after'][] = $this->get_email_value($this->location_type_organisation, $email, 'after');
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
   * not needed, but will be called. Values will be taken from other function
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
        break;
      case 'before':
        $result['Website'] = $this->civi_data_before['Website'];
        break;
      default:
        throw new Exception("Invalid Type '{$type}' in get_changed_Website_values");
    }
    return $result;
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
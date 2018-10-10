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
 * Class CRM_Nav_Data_NavContactMatcherCivi
 */
class CRM_Nav_Data_NavContactMatcherCivi {

  private $navision_custom_field;


  private $field_mapping;

  /**
   * CRM_Nav_Data_NavContactMatcherCivi constructor.
   *
   * @param $navision_custom_id
   * @param $org_name_1
   * @param $org_name_2
   */
  public function __construct($navision_custom_id, $org_name_1, $org_name_2) {
    $this->navision_custom_field = $navision_custom_id;
    $this->field_mapping = array(
      'No'                            => $this->navision_custom_field,
      'Address'                        => 'street_address',
      'Address_2'                      => 'supplemental_address_1',
      'City'                          => 'city',
      'Phone_No'                      => 'phone',  // phone_type_id = Phone, type organisation
      'Country_Region_Code'           => 'country_id',
      'Fax_No'                        => 'phone',  // phone_type_id = Fax, type Org
      'Post_Code'                     => 'postal_code',
      'E_mail'                        => 'email', // primary
      'Home_Page'                     => 'url', // Entity Website,  website_type_id = private
      'Type'                          => 'contact_type', // TODO : add this in NavContactRecord, can be Company and Person
      'Company_No'                    => $this->navision_custom_field,
      'Company_Name'                  => $org_name_1,
      'First_Name'                    => 'first_name',
      'Middle_Name'                   => 'middle_name',
      'Surname'                       => 'last_name',
      'Job_Title'                     => 'formal_title',
      'Mobile_Phone_No'               => 'phone', // location_type_id = org, phone_type_id = Mobile
      'Privat_Mobile_Phone_No'        => 'phone',
      'Salutation_Code'               => 'prefix_id',
      'E_mail_2'                      => 'email', // private
      //    'Delete_Flag'                 => '', // this shouldn't be needed
      'Company_Name_2'                => $org_name_2,
      'Funktion'                      => 'job_title',   // TODO --> what is this?
      'Geburtsdatum'                  => 'birth_date',
      //    'Postfach'                    => '',  TODO: how to display/import to CIviCRM
      //    'PLZ_Postfach'                => '',
      //    'Ort_Postfach'                => '',
      'Private_Telefonnr'             => 'phone', // Phone, private
      'Private_Faxnr'                 => 'phone', // Fax, private
      'Private_E_Mail'                => 'email', // Email, privat
      'Company_Adress'                => 'street_address',
      'Company_Adress_2'              => 'supplemental_address_1',
      'Company_Post_Code'             => 'postal_code',
      'Company_City'                  => 'city',
      'Company_Country_Region_Code'   => 'country_id',
    );
  }

  /**
   * @param $contact_type
   *
   * @return array
   * @throws \Exception
   */
  public function get_contact_fields($contact_type){
    switch ($contact_type) {
      case 'Individual':
        return array('No', 'Type', 'First_Name', 'Middle_Name', 'Surname', 'Job_Title');
      case 'Organisation':
        return array('Company_No', 'Company_Name', 'Company_Name_2');
      default:
        throw new Exception("Invalid Contact Type {$contact_type}. Please provide valid contact Type ('individual|organisation'");
    }
  }

  /**
   * @param $locationType
   *
   * @return array
   * @throws \Exception
   */
  public function get_address_fields($locationType) {
    switch($locationType) {
      case 'organisation':
        return array('Company_Adress', 'Company_Adress_2','Company_Post_Code','Company_City','Company_Country_Region_Code');
      case 'private':
        return array('Address', 'Address_2', 'City', 'Country_Region_Code', 'Post_Code' );
      default:
        throw new Exception("Invalid locationType {$locationType}. Please provide a valid locationType");
    }
  }

  public function get_phone_fields($locationType) {
    switch ($locationType) {
      case 'organisation':
        return array('Phone_No');
      case 'private':
        return array('Private_Telefonnr');
      case 'mobile':
        return array('Mobile_Phone_No');
      case 'mobile_private':
        return array('Privat_Mobile_Phone_No');
      default:
        throw new Exception("Invalid locationType for get_fax with {$locationType}. Please provide a valid locationType");
    }
  }

  /**
   * @param $locationType
   *
   * @return array
   * @throws \Exception
   */
  public function get_fax_fields($locationType) {
    switch ($locationType) {
      case 'organisation':
        return array('Fax_No');
      case 'private':
        return array('Private_Faxnr');
      default:
        throw new Exception("Invalid locationType for get_phone with {$locationType}. Please provide a valid locationType");
    }
  }

  /**
   * @param $locationType
   *
   * @return array
   * @throws \Exception
   */
  public function get_email_fields($locationType) {
    switch ($locationType) {
      case 'organisation':
        return array('E_Mail', 'E_Mail_2');
      case 'private':
        return array('Private_E_Mail');
      default:
        throw new Exception("Invalid locationType for get_phone with {$locationType}. Please provide a valid locationType");
    }
  }

  /**
   * @param string $locationType
   *
   * @return string
   * @throws \Exception
   */
  public function get_website_field($locationType = 'organisation') {
    switch ($locationType) {
      case 'organisation':
        return 'Home_Page';
      default:
        throw new Exception("Invalid locationType for get_website with {$locationType}. Please provide a valid locationType");
    }
  }

  /**
   * @param $nav_index
   *
   * @return mixed
   * @throws \Exception
   */
  public function get_civi_values($nav_index) {
    if (!isset($this->field_mapping[$nav_index])) {
      throw new Exception("Invalid Index '{$nav_index}'. Dataset invalid or mapping has changed.");
    }
    return $this->field_mapping[$nav_index];
  }

}

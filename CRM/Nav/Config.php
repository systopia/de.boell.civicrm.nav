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
 * Class CRM_Nav_Config
 */
class CRM_Nav_Config {

  private static $local        = TRUE;

  public static  $filter       = [
    '0001-01-01',
    '_blank_',
  ];

  private static $local_config = [
    'hbs_contact_id'                => '4',
    'navision_custom_field'         => 'custom_41',
    'process_id'                    => 'custom_172',
    'org_name_1'                    => 'custom_106',
    'org_name_2'                    => 'custom_107',
    'location_type_private'         => '6',
    'location_type_organisation'    => '8',
    'creditor_custom_field_id'      => 'custom_42',
    'debitor_custom_field_id'       => 'custom_43',
    'Vertrauensdozent_in'           => '14',
    'Stipendiat_in'                 => '12',
    'Promotionsstipendiat_in'       => '11',
    'Auswahlkommissionsmitglied'    => '13',
    'Allowance_to'                  => 'custom_174',
    'Angestrebter_Studienabschluss' => 'custom_176',
    'Process_Entry_No'              => 'custom_172',
    'Candidature_Process_Code'      => 'custom_173',
    'Hauptfach_1'                   => 'custom_178',
    'Subject_Group'                 => 'custom_181',
    'Field_of_Study'                => 'custom_182',
    'Promotionsfach'                => 'custom_179',
    'Promotionsthema'               => 'custom_180',
    'Project_Controller'            => 'custom_183',
    'Consultant'                    => 'custom_184',
    'Next_Report_to'                => 'custom_175',
    'Subsidie'                      => 'custom_177',
    'Graduation'                    => '11',
    'Study'                         => '12',
  ];

  private static $hbs_config   = [
    'hbs_contact_id'                => '4',
    'navision_custom_field'         => 'custom_147',
    'process_id'                    => 'custom_126',
    'org_name_1'                    => 'custom_45',
    'org_name_2'                    => 'custom_46',
    'location_type_private'         => '6',
    'location_type_organisation'    => '8',
    'creditor_custom_field_id'      => 'custom_164',
    'debitor_custom_field_id'       => 'custom_165',
    'Vertrauensdozent_in'           => '15',
    'Stipendiat_in'                 => '12',
    'Promotionsstipendiat_in'       => '11',
    'Auswahlkommissionsmitglied'    => '14',
    'Allowance_to'                  => 'custom_139',
    'Angestrebter_Studienabschluss' => 'custom_130',
    'Process_Entry_No'              => 'custom_126',
    'Candidature_Process_Code'      => 'custom_127',
    'Hauptfach_1'                   => 'custom_132',
    'Subject_Group'                 => 'custom_140',
    'Field_of_Study'                => 'custom_141',
    'Promotionsfach'                => 'custom_133',
    'Promotionsthema'               => 'custom_134',
    'Project_Controller'            => 'custom_137',
    'Consultant'                    => 'custom_138',
    'Next_Report_to'                => 'custom_129',
    'Subsidie'                      => 'custom_131',
    'Graduation'                    => '11',
    'Study'                         => '12',
  ];

  /**
   * @param $attribute
   *
   * @return mixed|string
   */
  public static function get($attribute) {
    if (self::$local) {
      if (isset(self::$local_config[$attribute])) {
        return self::$local_config[$attribute];
      }
      return "";
    }
    if (isset(self::$local_config[$attribute])) {
      return self::$hbs_config[$attribute];
    }
    return "";
  }

  /**
   * @return bool
   */
  public static function local() {
    return self::$local;
  }
}
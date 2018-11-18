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

class CRM_Nav_ChangeTracker_TableDescriptions {

  private static $contact_fields = [
    'id',
    'contact_type',
    'contact_sub_type',
    'do_not_email',
    'do_not_phone',
    'do_not_mail',
    'do_not_sms',
    'do_not_trade',
    'is_opt_out',
    'legal_identifier',
    'external_identifier',
    'sort_name',
    'display_name',
    'nick_name',
    'legal_name',
    'image_URL',
    'preferred_communication_method',
    'preferred_language',
    'preferred_mail_format',
    'hash',
    'api_key',
    'source',
    'first_name',
    'middle_name',
    'last_name',
    'prefix_id',
    'suffix_id',
    'formal_title',
    'communication_style_id',
    'email_greeting_id',
    'email_greeting_custom',
    'email_greeting_display',
    'postal_greeting_id',
    'postal_greeting_custom',
    'postal_greeting_display',
    'addressee_id',
    'addressee_custom',
    'addressee_display',
    'job_title',
    'gender_id',
    'birth_date',
    'is_deceased',
    'deceased_date',
    'household_name',
    'primary_contact_id',
    'organization_name',
    'sic_code',
    'user_unique_id',
    'employer_id',
    'is_deleted',
    'created_date',
    'modified_date',
    'log_date',
    'log_conn_id',
    'log_user_id',
    'log_action',
  ];

  public static function get_Contact_fields() {
    return self::$contact_fields;
  }

  public static function get_Address_fields() {
    return [];
  }
}
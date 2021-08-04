<?php
/*-------------------------------------------------------+
| Navision API Tools                                     |
| Heinrich Böll Stiftung                                 |
| Copyright (C) 2021 SYSTOPIA                            |
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
 * No Mapping available
 * Class CRM_Nav_EmptyStudienbereich_Mapping
 */
class CRM_Nav_EmptyStudienbereichMapping extends Exception
{}

/**
 * Invalid mapping Key
 * Class CRM_Nav_InvalidMappingKey
 */
class CRM_Nav_InvalidMappingKey extends Exception
{}

/**
 * Api Error
 * Class CRM_Nav_InternalApiError
 */
class CRM_Nav_InternalApiError extends Exception
{}

/**
 * Mapping in CiviCRM not found
 * Class CRM_Nav_MappingNotFound
 */
class CRM_Nav_MappingNotFound extends Exception
{}

/**
 * More than one Match found for that mapping
 * Class CRM_Nav_MultipleMappingMatches
 */
class CRM_Nav_MultipleMappingMatches extends Exception
{}
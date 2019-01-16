<?php
/*-------------------------------------------------------+
| Amnesty Iternational Vlaanderen Custom API             |
| Copyright (C) 2019 SYSTOPIA                            |
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
 * Process AivlSelfservice.contactbyhash
 *
 *  Frontend for Contact.getsingle query by hash value
 *
 * @param array see specs below (_civicrm_api3_engage_signpetition_spec)
 * @return array API result array
 * @access public
 */
function civicrm_api3_aivl_selfservice_contactbyhash($params) {
  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'Selfservice.contactbyhash');

  if (!empty($params['hash'])) {
    try {
      return civicrm_api3('Contact', 'getsingle', [
          'hash' => $params['hash'],
          'check_permissions' => 0,
      ]);
    } catch (CiviCRM_API3_Exception $ex) {
      // not found
      return civicrm_api3_create_error("Not found");
    }
  } else {
    return civicrm_api3_create_error("Missing only parameter 'hash'.");
  }
}

/**
 * Adjust Metadata for AivlSelfservice.contactbyhash
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_selfservice_contactbyhash_spec(&$params) {
  // CONTACT BASE
  $params['hash'] = array(
      'name'         => 'hash',
      'api.required' => 1,
      'title'        => 'Contact Hash',
      'description'  => 'Needs to be valid',
  );
}

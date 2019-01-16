<?php
/*-------------------------------------------------------+
| Amnesty Iternational Vlaanderen Custom API             |
| Copyright (C) 2018 SYSTOPIA                            |
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
 * Process AivlSelfservice.contactdata (see AIVL-3486):
 *
 *  Update contact's data using XCM/I3Val
 *
 * @param see specs below (_civicrm_api3_engage_signpetition_spec)
 * @return array API result array
 * @access public
 */
function civicrm_api3_aivl_selfservice_contactdata($params) {
  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'Selfservice.contactdata');

  if (!empty($params['contact_id'])) {
    // contact_id is given -> just call I3Val
    $params['id'] = (int) $params['contact_id'];
    $params['check_permissions'] = 0;
    return civicrm_api3('Contact', 'request_update', $params);

  } else {
    // contact was NOT identified - maybe just run through XCM and then through I3Val
    $params['id'] = CRM_Aivlapi_Processor::getContactID($params);
    $params['check_permissions'] = 0;
    return civicrm_api3('Contact', 'request_update', $params);
  }
}

/**
 * Adjust Metadata for Payment action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_selfservice_contactdata_spec(&$params) {
  // CONTACT BASE
  $params['hash'] = array(
      'name'         => 'hash',
      'api.required' => 0,
      'title'        => 'Contact Hash',
      'description'  => 'If given, triggers update',
  );
  $params['email'] = array(
    'name'           => 'email',
    'api.required'   => 0,
    'title'          => 'email address',
    );
  $params['first_name'] = array(
      'name'         => 'first_name',
      'api.required' => 0,
      'title'        => 'First Name',
  );
  $params['last_name'] = array(
      'name'         => 'last_name',
      'api.required' => 0,
      'title'        => 'Last Name',
  );
  $params['birth_date'] = array(
      'name'         => 'birth_date',
      'api.required' => 0,
      'title'        => 'Birth Date',
  );
}

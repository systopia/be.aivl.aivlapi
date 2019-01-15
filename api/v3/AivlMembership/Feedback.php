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
 * Process AivlMembership.feedback (see AIVL-3690)
 *
 * @param see specs below (_civicrm_api3_engage_signpetition_spec)
 * @return array API result array
 * @access public
 */
function civicrm_api3_aivl_membership_feedback($params) {

  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlMembership.feedback');

  switch ($params['feedback']) {
    case 'extend':
      return CRM_Aivlapi_MembershipProcessor::extendMembership($params);

    case 'stop':
      return CRM_Aivlapi_MembershipProcessor::stopMembership($params);

    default:
      return civicrm_api3_create_error("Unknown feedback '{$params['feedback']}'!");
  }
}

/**
 * Adjust Metadata for Payment action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_membership_feedback_spec(&$params) {
  // CONTACT BASE
  $params['feedback'] = array(
      'name'         => 'feedback',
      'api.required' => 1,
      'title'        => 'Feedback type',
      'description'  => 'expects "extend" or "stop"',
  );
  $params['contact_id'] = array(
      'name'         => 'contact_id',
      'api.required' => 0,
      'title'        => 'Contact ID',
      'description'  => 'if given, will trigger updates',
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
}

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
 * Process AivlEvent.register
 *
 * @param see specs below (_civicrm_api3_engage_signpetition_spec)
 * @return array API result array
 * @access public
 */
function civicrm_api3_aivl_event_register($params) {

  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlEvent.register');

  // resolve contact
  CRM_Aivlapi_Processor::resolveContact($params);

  // now basically create a participant
  return civicrm_api3('Participant', 'create', $params);
}

/**
 * Adjust Metadata for Payment action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_event_register_spec(&$params) {
  // CONTACT BASE
  $params['event_id'] = array(
    'name'         => 'event_id',
    'api.required' => 1,
    'title'        => 'Event to sign up to',
    );
}

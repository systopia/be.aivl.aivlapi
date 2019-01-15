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
 * @param see specs below
 * @return array API result array
 * @access public
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_aivl_petition_sign($params) {

  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlPetition.sign');

  // resolve contact
  CRM_Aivlapi_Processor::resolveContact($params);

  // add to groups
  CRM_Aivlapi_Processor::processGroupSignup($params);

  // run through processor
  $result = CRM_Aivlapi_PetitionProcessor::signPetitions($params);

  // handle errors
  if (!empty($result['error'])) {
    CRM_Core_Error::debug_log_message("'AivlPetition.sign': {$result['error']}");
    return civicrm_api3_create_error("Error while signing petitions: {$result['error']}");
  }

  // return results
  return civicrm_api3_create_success("Signed {$result['counter_signed']} petitions, {$result['counter_already']} were already signed.");
}

/**
 * Adjust Metadata for Payment action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_petition_sign_spec(&$params) {
  $params['campaign_id'] = array(
    'name'         => 'campaign_id',
    'api.required' => 0,
    'title'        => 'Campaign ',
    );
}

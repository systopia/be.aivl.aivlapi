<?php
use CRM_Aivlapi_ExtensionUtil as E;

/**
 * AivlEmailPreferences.Set API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_aivl_email_preferences_Set_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => E::ts('Contact ID'),
    'description' => E::ts('Contact ID'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * AivlEmailPreferences.Set API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_aivl_email_preferences_Set($params) {
  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlEmailPreferences.set');
  // run through processor
  $result = CRM_Aivlapi_EmailPreferencesProcessor::set($params);
  // handle errors
  if (!empty($result['error'])) {
    Civi::log()->warning(E::ts("'AivlEmailPreferences.set': {$result['error']}"));
    return civicrm_api3_create_error(E::ts("Error while setting email preferences: {$result['error']}"));
  }
  // return results
  return civicrm_api3_create_success(E::ts('Email Preferences received'), $params, "AivlEmailPreferences", "set");
}

<?php
use CRM_Aivlapi_ExtensionUtil as E;

/**
 * AivlEmailPreferences.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_aivl_email_preferences_Get_spec(&$spec) {
  $spec['hash'] = [
    'name' => 'hash',
    'title' => E::ts('Contact Hash'),
    'description' => E::ts('Contact Hash'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
  ];}

/**
 * AivlEmailPreferences.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_aivl_email_preferences_Get($params) {
  return CRM_Aivlapi_EmailPreferencesProcessor::get(trim(stripslashes(strip_tags($params['hash']))));
}

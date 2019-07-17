<?php
use CRM_Aivlapi_ExtensionUtil as E;

/**
 * AivlBasic.Signup API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_aivl_basic_signup_spec(&$spec) {
  $spec['first_name'] = [
    'name' => 'first_name',
    'title' => E::ts('First Name'),
    'description' => E::ts('First Name'),
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['last_name'] = [
    'name' => 'last_name',
    'title' => E::ts('Last Name'),
    'description' => E::ts('Last Name'),
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['email'] = [
    'name' => 'email',
    'title' => E::ts('Email'),
    'description' => E::ts('Email'),
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['birth_date'] = [
    'name' => 'birth_date',
    'title' => E::ts('Date of Birth'),
    'description' => E::ts('Date of Birth'),
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
}

/**
 * AivlBasic.Signup API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_aivl_basic_signup($params) {
  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlBasic.signup');
  // resolve contact
  CRM_Aivlapi_Processor::resolveContact($params);
  // add to groups
  CRM_Aivlapi_Processor::processGroupSignup($params);
  // run through processor
  $result = CRM_Aivlapi_BasicSignupProcessor::basicSignup($params);
  // handle errors
  if (!empty($result['error'])) {
    Civi::log()->debug(E::ts("'AivlPetition.sign': {$result['error']}"));
    return civicrm_api3_create_error(E::ts("Error while doing a basic signup: {$result['error']}"));
  }
  // return results
  return civicrm_api3_create_success(E::ts("Signed {$result['counter_signed']} petitions, {$result['counter_already']} were already signed."));
}

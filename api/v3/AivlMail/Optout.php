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
 * Process AivlMail.unsubscribe (see AIVL-2824)
 *
 * @param see specs below (_civicrm_api3_engage_signpetition_spec)
 * @return array API result array
 * @access public
 */
function civicrm_api3_aivl_mail_optout($params) {

  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlMail.optout');

  // get contacts (by email-address)
  $contacts = CRM_Aivlapi_MailProcessor::getContacts($params);

  // unsubscribe all of them
  CRM_Aivlapi_MailProcessor::optoutContacts($contacts, $params);

  // send email
  CRM_Aivlapi_MailProcessor::sendOptOutMail($contacts, $params);

  // return success
  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for Payment action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_mail_optout_spec(&$params) {
  // CONTACT BASE
  $params['email'] = array(
    'name'         => 'email',
    'api.required' => 1,
    'title'        => 'email address to unsubscribe',
    );
  $params['template_id'] = array(
      'name'         => 'template_id',
      'api.required' => 0,
      'title'        => 'template to send as the opted-out message',
  );
  $params['bcc'] = array(
      'name'         => 'bcc',
      'api.required' => 0,
      'title'        => 'BCC emails for the opted-out message',
  );
}

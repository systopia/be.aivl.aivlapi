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
 * Will sanitise the API input (for RapidMiner artifacts):
 *  - drop parameters with '?' value
 */
class CRM_Aivlapi_ParameterSanitation implements API_Wrapper {
  /**
   * the wrapper contains a method that allows you to alter the parameters of the api request (including the action and the entity)
   */
  public function fromApiInput($apiRequest) {
    // remove the sentinel parameter
    if (isset($apiRequest['params']['drop_questionmarks'])) {
      unset($apiRequest['params']['drop_questionmarks']);
    }

    CRM_Core_Error::debug_log_message("DQ-Request: " . json_encode($apiRequest));

    // get the submitted parameter keys
    $keys = array_keys($apiRequest['params']);

    // move all 'questionmark_XXX' parameters to the suffix, if set
    foreach ($keys as $key) {
      if (substr($key, 0, 13) == 'questionmark_') {
        if (isset($apiRequest['params'][$key]) && $apiRequest['params'][$key] != '?' && $apiRequest['params'][$key] != '') {
          // move this value to the new key
          $new_key = substr($key, 13);
          $apiRequest['params'][$new_key] = $apiRequest['params'][$key];
        }
        unset($apiRequest['params'][$key]);
      }
    }

    // drop all parameters with value '?'
    foreach ($keys as $key) {
      if ($apiRequest['params'][$key] == '?') {
        unset($apiRequest['params'][$key]);
      }
    }

    CRM_Core_Error::debug_log_message("DQ-Request done: " . json_encode($apiRequest));

    return $apiRequest;
  }

  /**
   * alter the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    return $result;
  }
}

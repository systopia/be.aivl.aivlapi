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
        // we want to unset this parameter, but what if we bypassed a 'required' parameter with the '?'
        //  and now removing it would trip up the target code? This here is happening _after_
        //  validation but _before_ execution.
        //  Therefore, wo would like to throw an exception, if this violates the parameter specs
        $fields = $this->getEntityActionFields($apiRequest['entity'], $apiRequest['action']);
        if (isset($fields[strtolower($key)])) {
          $field_spec = $fields[strtolower($key)];
          if (!empty($field_spec['api.required']) || !empty($field_spec['required'])  || !empty($field_spec['is_required'])) {
            // this would delete/unset a required parameter -> we don't want that.
            throw new API_Exception("Mandatory key(s) missing from params array: " . $key);
          }
        }
        unset($apiRequest['params'][$key]);
      }
    }

    return $apiRequest;
  }

  /**
   * alter the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    return $result;
  }

    /**
     * Helper function to return cached entity action fields
     *
     * @param string $entity
     *      the API entity
     *
     * @param string $action
     *      the API entity
     *
     * @return array
     *      list of parameter => specs option
     *
     * @throws Exception
     *      in the unlikely event that the API getfields call fails
     */
  protected function getEntityActionFields($entity, $action) {
      static $field_spec_cache = [];
      if (!isset($field_spec_cache[$entity][$action])) {
          $field_specs = civicrm_api3($entity, 'getfields', ['api_action' => $action]);
          $field_spec_cache[$entity][$action] = $field_specs['values'];
      }
      return $field_spec_cache[$entity][$action];
  }
}

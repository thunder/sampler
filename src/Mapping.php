<?php

namespace Drupal\sampler;

/**
 * The Mapping service class.
 *
 * @package Drupal\sampler
 */
class Mapping {

  /**
   * Store if data should be anonymized.
   *
   * @var bool
   */
  protected $anonymize;

  /**
   * Mapping of group names to integer values.
   *
   * @var string[]
   */
  protected $mapping = [];

  /**
   * Map a bundle of an entity to an anonymized value.
   *
   * When the mapping is created, the entity type is used as namespace for the
   * mapped value. This means, that the same bundle name can have different
   * mapped values for different entity types.
   *
   * @param string $entityTypeId
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   *
   * @return string
   *   The mapped role name.
   */
  public function getBundleMapping(string $entityTypeId, string $bundle) {
    return $this->getMapping($entityTypeId, $bundle, 'bundle');
  }

  /**
   * Map a user role to an anonymized value.
   *
   * @param string $role
   *   The field name.
   *
   * @return string
   *   The mapped role name.
   */
  public function getUserRoleMapping(string $role) {
    return $this->getMapping('role', $role, 'role');
  }

  /**
   * Map a field name to an anonymized value.
   *
   * When the mapping is created, the entity type is used as namespace for the
   * mapped value. This means, that the same field name can have different
   * mapped values for different entity types.
   *
   * @param string $entityTypeId
   *   The entity type.
   * @param string $fieldName
   *   The field name.
   *
   * @return string
   *   The mapped field name.
   */
  public function getFieldMapping(string $entityTypeId, $fieldName) {
    return $this->getMapping($entityTypeId, $fieldName, 'field');
  }

  /**
   * Get mapping of a value.
   *
   * Values, that should be mapped for anonymization, can be mapped to an
   * arbitrary value. All mappings are namespaced, to have different mappings
   * for a value in different contexts.
   * Examples would be mapping of bundle or field names names for different
   * entities or roles for users.
   * The mapped value will always be constructed out of the prefix and a
   * consecutively increasing number.
   *
   * @param string $nameSpace
   *   The namespace of the mapping.
   * @param string $value
   *   The value to map.
   * @param string $prefix
   *   The prefix.
   *
   * @return string
   *   The mapped value.
   */
  private function getMapping(string $nameSpace, string $value, $prefix) {
    if ($this->anonymize === FALSE) {
      return $value;
    }

    if (!isset($this->mapping[$nameSpace])) {
      $this->mapping[$nameSpace] = [$value => $prefix . '-' . 0];
      return $this->mapping[$nameSpace][$value];
    }

    if (!isset($this->mapping[$nameSpace][$value])) {
      $this->mapping[$nameSpace][$value] = $prefix . '-' . count($this->mapping[$nameSpace]);
    }

    return $this->mapping[$nameSpace][$value];
  }

  /**
   * Set anonymize flag.
   *
   * If set to true, data used in keys will be anonymized in output.
   * Otherwise the machine names will be printed.
   *
   * @param bool $anonymize
   *   Anonymize or not.
   */
  public function anonymize(bool $anonymize) {
    $this->anonymize = $anonymize;
  }

}

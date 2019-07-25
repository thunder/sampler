<?php

namespace Drupal\sampler;

/**
 * The Mapping class.
 *
 * @package Drupal\sampler
 */
class Mapping {

  /**
   * Store if data should be anonymized.
   *
   * @var bool
   */
  protected $enabled = TRUE;

  /**
   * Mapping of group names to integer values.
   *
   * @var string[]
   */
  protected $mapping = [];

  /**
   * Prefix for mapped entity bundles.
   */
  protected const BUNDLE_PREFIX = 'bundle';

  /**
   * Prefix for mapped user roles.
   */
  protected const ROLE_PREFIX = 'role';

  /**
   * Prefix for mapped field names.
   */
  protected const FIELD_PREFIX = 'field';

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
    return $this->getMapping($entityTypeId, $bundle, self::BUNDLE_PREFIX);
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
    return $this->getMapping('role', $role, self::ROLE_PREFIX);
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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFieldMapping(string $entityTypeId, $fieldName) {
    return $this->getMapping($entityTypeId, $fieldName, self::FIELD_PREFIX);
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
   * @param string $nameSpaceSuffix
   *   Custom part of namespace of the mapping.
   * @param string $value
   *   The value to map.
   * @param string $prefix
   *   The prefix, used for namespace and mapped value.
   *
   * @return string
   *   The mapped value.
   */
  private function getMapping(string $nameSpaceSuffix, string $value, $prefix) {
    if ($this->enabled === FALSE) {
      return $value;
    }

    $nameSpace = $prefix . $nameSpaceSuffix;
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
   * Set enabled flag.
   *
   * Mapping is only enabled, if this is set to true.
   * Otherwise the original value will be return.
   *
   * @param bool $enableMapping
   *   Enable mapping or not.
   */
  public function enableMapping(bool $enableMapping) {
    $this->enabled = $enableMapping;
  }

}

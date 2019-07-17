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
   * Prefix for anonymized names.
   */
  private const MAPPING_PREFIX = 'mapped-';

  /**
   * Get mapping of a value.
   *
   * Values, that should be mapped for anonymization, can be mapped to an
   * arbitrary value. All mappings are namespaced, to have different mappings
   * for a value in different contexts.
   * Examples would be mapping of bundle or field names names for different
   * entities or roles for users.
   * The mapped value will always be constructed out of the MAPPING_PREFIX and a
   * consecutively increasing number.
   *
   * @param string $nameSpace
   *   The namespace of the mapping.
   * @param string $value
   *   The value to map.
   *
   * @return string
   *   The mapped value.
   */
  public function getMapping(string $nameSpace, string $value) {
    if ($this->anonymize === FALSE) {
      return $value;
    }

    if (!isset($this->mapping[$nameSpace])) {
      $this->mapping[$nameSpace] = [$value => self::MAPPING_PREFIX. 0];
      return $this->mapping[$nameSpace][$value];
    }

    if (!isset($this->mapping[$nameSpace][$value])) {
      $this->mapping[$nameSpace][$value] = self::MAPPING_PREFIX . count($this->mapping[$nameSpace]);
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

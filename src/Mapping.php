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
  private const MAPPING_PREFIX = 'mapping-';

  /**
   * Get mapped value of a group.
   *
   * @param string $entityTypeId
   *   The grouped entity.
   * @param string $group
   *   The group to map.
   *
   * @return string
   *   The mapped value.
   */
  public function getMapping(string $entityTypeId, string $group) {
    if ($this->anonymize === FALSE) {
      return $group;
    }

    if (!isset($this->mapping[$entityTypeId])) {
      $this->mapping[$entityTypeId] = [$group => self::MAPPING_PREFIX . 0];
      return $this->mapping[$entityTypeId][$group];
    }

    if (!isset($this->mapping[$entityTypeId][$group])) {
      $this->mapping[$entityTypeId][$group] = self::MAPPING_PREFIX . count($this->mapping[$entityTypeId]);
    }

    return $this->mapping[$entityTypeId][$group];
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

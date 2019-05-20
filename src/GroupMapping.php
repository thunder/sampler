<?php

namespace Drupal\sampler;

/**
 * The GroupMapping service class.
 *
 * @package Drupal\sampler
 */
class GroupMapping {

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
  protected $groupMapping = [];

  /**
   * Prefix for anonymized group names.
   */
  private const GROUP_PREFIX = 'group-';

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
  public function getGroupMapping(string $entityTypeId, string $group): string {
    if ($this->anonymize === FALSE) {
      return $group;
    }

    if (!isset($this->groupMapping[$entityTypeId])) {
      $this->groupMapping[$entityTypeId] = [$group => self::GROUP_PREFIX . 0];
      return $this->groupMapping[$entityTypeId][$group];
    }

    if (!isset($this->groupMapping[$entityTypeId][$group])) {
      $this->groupMapping[$entityTypeId][$group] = self::GROUP_PREFIX . count($this->groupMapping[$entityTypeId]);
    }

    return $this->groupMapping[$entityTypeId][$group];
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

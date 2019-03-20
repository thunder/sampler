<?php

namespace Drupal\sampler\Traits;

/**
 * Provides methods for handling of grouped data.
 */
trait GroupedDataTrait {

  /**
   * Mapping of group names to integer values.
   *
   * @var string[]
   */
  protected $groupMapping = [];

  /**
   * Map group names to an integer value.
   *
   * The mapped integer will be used in output instead of the group name.
   *
   * @param string $entityTypeId
   *   The grouped entity.
   * @param string $group
   *   The group to map.
   * @param int $mapping
   *   The id to use as mapping, must be an integer and unique.
   *
   * @throws \InvalidArgumentException
   */
  protected function setGroupMapping(string $entityTypeId, string $group, int $mapping) {
    if (!isset($this->groupMapping[$entityTypeId])) {
      $this->groupMapping[$entityTypeId] = [];
    }
    if (in_array($mapping, $this->groupMapping[$entityTypeId])) {
      throw new \InvalidArgumentException('Mapping already exists');
    }

    $this->groupMapping[$group] = $mapping;
  }

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
  protected function getGroupMapping($entityTypeId, $group): string {
    if ($this->anonymize === FALSE) {
      return $group;
    }

    if (!isset($this->groupMapping[$entityTypeId])) {
      $this->groupMapping[$entityTypeId] = [$group => 'group-0'];
      return $this->groupMapping[$entityTypeId][$group];
    }

    if (!isset($this->groupMapping[$entityTypeId][$group])) {
      $last = end($this->groupMapping[$entityTypeId]);
      $this->groupMapping[$entityTypeId][$group] = ++$last;
    }

    return $this->groupMapping[$entityTypeId][$group];
  }

}

<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for count plugins.
 */
interface CountInterface extends PluginInspectionInterface {

  /**
   * Collects data for the provided entity type ID.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return integer
   *   The collected information.
   */
  public function collect(int $entityTypeId);

  /**
   * The key of the data in the result.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return string
   */
  public function key(int $entityTypeId): string;

}

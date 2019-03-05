<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for entity data plugins.
 */
interface SamplerCountInterface extends PluginInspectionInterface {

  /**
   * Collects data for the provided entity type ID.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return array
   *   The data array.
   */
  public function collect($entityTypeId);

}

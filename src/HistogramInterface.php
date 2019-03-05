<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Histogram plugins.
 */
interface HistogramInterface extends PluginInspectionInterface {

  /**
   * Checks if the current entity type id is supported by this plugin.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return bool
   *   Supported or not.
   */
  public function isApplicable($entityTypeId);

  /**
   * Build histogram for the provided entity type ID.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return array
   *   The histogram array.
   */
  public function collect($entityTypeId);

}

<?php

namespace Drupal\sampler;

/**
 * Base class for Histogram sampler plugins.
 */
abstract class HistogramBase extends SamplerBase {

  /**
   * {@inheritdoc}
   */
  public function key($entityTypeId) {
    return 'histogram';
  }

}

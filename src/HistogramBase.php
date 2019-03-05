<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 05.03.19
 * Time: 17:20
 */

namespace Drupal\sampler;

abstract class HistogramBase extends SamplerBase {

  /**
   * {@inheritdoc}
   */
  public function key($entityTypeId) {
    return 'histogram';
  }

}

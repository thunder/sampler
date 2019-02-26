<?php

namespace Drupal\sampler\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Histogram plugin item annotation object.
 *
 * @see \Drupal\sampler\HistogramManager
 * @see plugin_api
 *
 * @Annotation
 */
class SamplerHistogram extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}

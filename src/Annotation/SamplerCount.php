<?php

namespace Drupal\sampler\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a count plugin item annotation object.
 *
 * @see \Drupal\sampler\CountPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class SamplerCount extends Plugin {


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

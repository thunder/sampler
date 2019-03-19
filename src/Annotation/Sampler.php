<?php

namespace Drupal\sampler\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a sampler plugin item annotation object.
 *
 * @see \Drupal\sampler\SamplerPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Sampler extends Plugin {


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

  /**
   * The entity type ID.
   *
   * @var string
   */
  // phpcs:disable
  public $entity_type_id;
  // phpcs:enable

}

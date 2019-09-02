<?php

namespace Drupal\sampler\Plugin\Derivative;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Derives Sampler bundle plugin instances.
 */
class BundleDeriver extends SamplerDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Reset the discovered definitions.
    $this->derivatives = [];

    foreach ($this->getSupportedEntityTypeDefinitions() as $entity_type) {
      // We need fieldable entities, assume SQL storage and they have a bundle
      // key.
      if ($entity_type->entityClassImplements(FieldableEntityInterface::class)
        && is_subclass_of($entity_type->getStorageClass(), SqlEntityStorageInterface::class)
        && $entity_type->getKey('bundle')
      ) {
        $this->derivatives[$entity_type->id()] = $base_plugin_definition;
        $this->derivatives[$entity_type->id()]['admin_label'] = $this->t('@entity_type bundles', ['@entity_type' => $entity_type->getLabel()]);
        $this->derivatives[$entity_type->id()]['entity_type_id'] = $entity_type->id();
        // @todo dependencies
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}

<?php

namespace Drupal\sampler\Plugin\Derivative;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Derives Sampler base field plugin instances.
 */
class BaseFieldDeriver extends SamplerDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Reset the discovered definitions.
    $this->derivatives = [];

    foreach ($this->getSupportedEntityTypeDefinitions() as $entity_type) {
      if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        $this->derivatives[$entity_type->id()] = $base_plugin_definition;
        $this->derivatives[$entity_type->id()]['admin_label'] = $this->t('@entity_type base fields', ['@entity_type' => $entity_type->getLabel()]);
        $this->derivatives[$entity_type->id()]['entity_type_id'] = $entity_type->id();
        // @todo dependencies
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}

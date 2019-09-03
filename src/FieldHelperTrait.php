<?php

namespace Drupal\sampler;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Trait for field helper functions.
 */
trait FieldHelperTrait {

  /**
   * Check, if current filed is a reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field configuration.
   *
   * @return bool
   *   Field is reference field.
   */
  protected function isReferenceField(FieldDefinitionInterface $fieldDefinition) {
    return in_array($fieldDefinition->getType(), ['entity_reference', 'entity_reference_revisions']);
  }

  /**
   * Check, if current field is an entity base field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field configuration.
   *
   * @return bool
   *   Field is base field.
   */
  protected function isBaseField(FieldDefinitionInterface $fieldDefinition) {
    return ($fieldDefinition instanceof BaseFieldDefinition  || $fieldDefinition instanceof BaseFieldOverride);
  }

}

<?php

namespace Drupal\sampler\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives Sampler base field plugin instances.
 */
class BaseFieldDeriver extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a BaseFieldDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Reset the discovered definitions.
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
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

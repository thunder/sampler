<?php

namespace Drupal\sampler\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a basic sampler deriver.
 */
abstract class SamplerDeriverBase extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The sampler settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $samplerSettings;

  /**
   * Constructs a BundleDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ImmutableConfig $sampler_settings
   *   The sampler settings.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ImmutableConfig $sampler_settings) {
    $this->entityTypeManager = $entity_type_manager;
    $this->samplerSettings = $sampler_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')->get('sampler.settings')
    );
  }

  /**
   * Get supported entity type definitions.
   *
   * By default only entities, that ship with Thunder are supported. Supported
   * entity types are defined in the sampler settings.
   *
   * @return bool
   *   Returns TRUE if entity type is supported.
   */
  protected function getSupportedEntityTypeDefinitions() {
    $entityTypeDefinitions = $this->entityTypeManager->getDefinitions();
    $supportedEntityTypes = array_flip($this->samplerSettings->get('supported_entity_types'));

    return array_intersect_key($entityTypeDefinitions, $supportedEntityTypes);
  }

}

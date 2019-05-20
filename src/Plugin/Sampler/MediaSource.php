<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\GroupMapping;
use Drupal\sampler\SamplerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects user data.
 *
 * @Sampler(
 *   id = "media_source",
 *   label = @Translation("Media source"),
 *   description = @Translation("Collects media source data."),
 *   entity_type_id = "media"
 * )
 */
class MediaSource extends SamplerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   *
   * Overrides the construction of sampler count plugins to inject some
   * services.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sampler\GroupMapping $group_mapping
   *   The group mapping service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, GroupMapping $group_mapping, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $group_mapping);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sampler.group_mapping'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function collect() {
    $entityTypeId = $this->entityTypeId();
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $bundleEntityType = $entityTypeDefinition->getBundleEntityType();

    $types = $this->entityTypeManager->getStorage($bundleEntityType)->loadMultiple();

    foreach ($types as $name => $type) {
      if ($type->getEntityType()->getProvider() === 'media_entity') {
        $source = $type->getType()->getPluginId();
      }
      else {
        $source = $type->getSource()->getPluginId();
      }

      $mapping = $this->groupMapping->getGroupMapping($entityTypeId, $name);
      $this->collectedData[$mapping]['source'] = $source;
    }

    return $this->collectedData;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return 'bundle';
  }

}

<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\sampler\FieldData;
use Drupal\sampler\Mapping;
use Drupal\sampler\SamplerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Counts base fields of entities.
 *
 * @Sampler(
 *   id = "base_fields",
 *   label = @Translation("Base fields"),
 *   description = @Translation("Collects base field count."),
 *   deriver = "\Drupal\sampler\Plugin\Derivative\BaseFieldDeriver"
 * )
 */
class BaseFields extends SamplerBase {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field data service.
   *
   * @var \Drupal\sampler\FieldData
   */
  protected $fieldData;

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
   * @param \Drupal\sampler\Mapping $mapping
   *   The group mapping service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\sampler\FieldData $fieldData
   *   The field data service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, Mapping $mapping, EntityFieldManagerInterface $entityFieldManager, FieldData $fieldData) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $mapping);

    $this->entityFieldManager = $entityFieldManager;
    $this->fieldData = $fieldData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sampler.mapping'),
      $container->get('entity_field.manager'),
      $container->get('sampler.field_data')
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

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition */
    foreach ($this->entityFieldManager->getBaseFieldDefinitions($entityTypeId) as $fieldDefinition) {
      $this->collectedData[$this->mapping->getFieldMapping($entityTypeId, $fieldDefinition->getName())] = $this->fieldData->collect($fieldDefinition, $entityTypeId);
    }

    return $this->collectedData;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): string {
    return $this->getBaseId();
  }

}

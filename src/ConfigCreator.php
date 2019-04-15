<?php

namespace Drupal\sampler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The ConfigCreator class.
 *
 * @package Drupal\sampler
 */
class ConfigCreator {

  /**
   * The report data.
   *
   * @var array
   */
  protected $reportData;

  /**
   * The reporter instance.
   *
   * @var \Drupal\sampler\Reporter
   */
  protected $reporter;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ConfigCreator object.
   */
  public function __construct(Reporter $reporter, EntityTypeManagerInterface $entityTypeManager) {
    $this->reporter = $reporter;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Sets report data.
   *
   * @param string $file
   *   A report file.
   *
   * @return \Drupal\sampler\ConfigCreator
   *   This class.
   */
  public function setReportData(string $file) : ConfigCreator {
    $this->reportData = Json::decode(file_get_contents($file));
    return $this;
  }

  /**
   * Deletes all bundle configs.
   *
   * @return \Drupal\sampler\ConfigCreator
   *   This class.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cleanup() : ConfigCreator {
    foreach ($this->getEntityTypes() as $entity_type) {
      $definition = $this->entityTypeManager->getDefinition($entity_type);
      if ($definition->getBundleEntityType()) {
        // Delete all entities.
        $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
        $this->entityTypeManager->getStorage($entity_type)->delete($entities);

        // Delete all bundles.
        $bundles = $this->entityTypeManager->getStorage($definition->getBundleEntityType())->loadMultiple();
        $this->entityTypeManager->getStorage($definition->getBundleEntityType())->delete($bundles);
      }
    }

    return $this;
  }

  /**
   * Creates new bundles.
   *
   * @return \Drupal\sampler\ConfigCreator
   *   This class.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function create(): ConfigCreator {

    foreach ($this->getEntityTypes() as $entity_type) {
      $entity_definition = $this->entityTypeManager->getDefinition($entity_type);
      if (!isset($this->reportData[$entity_type]['bundle'])) {
        continue;
      }
      foreach ($this->reportData[$entity_type]['bundle'] as $id => $bundle) {
        $bundle_definition = $this->entityTypeManager->getDefinition($entity_definition->getBundleEntityType());

        $bundle_entity = $this->entityTypeManager->getStorage($entity_definition->getBundleEntityType())->create([
          $bundle_definition->getKey('id') => $id,
          $bundle_definition->getKey('label') => $id,
        ]);
        $bundle_entity->save();
      }
    }

    return $this;
  }

  /**
   * Get a list of all supported entity types.
   *
   * @return array
   *   List of supported entity types.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getEntityTypes() : array {
    $existingData = $this->reporter->collect()->getReport();

    $entity_types = array_keys(array_intersect_key($this->reportData, $existingData));
    return array_diff($entity_types, [
      // TODO: Remove when crop type is exported.
      'crop',
      'update_helper_checklist_update',
      'access_token',
      'menu_link_content',
      'redirect',
    ]);
  }

}

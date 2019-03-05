<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\sampler\SamplerBase;

/**
 * Counts base fields of entities.
 *
 * @Sampler(
 *   id = "grouped_data",
 *   label = @Translation("Base fields"),
 *   description = @Translation("Collects base field count.")
 * )
 */
class GroupedData extends SamplerBase {

  /**
   * {@inheritdoc}
   */
  public function collect($entityTypeId) {
    $baseFields = array_keys($this->entityFieldManager->getBaseFieldDefinitions($entityTypeId));
    return count($baseFields);
  }

  /**
   * {@inheritdoc}
   */
  public function key($entityTypeId): string {
    return $this->getGroupingSettings($entityTypeId)['groupKey'];
  }

  /**
   * Get some settings for grouping entities.
   *
   * @param string $entityTypeId
   *   The entity type to get settings for.
   *
   * @return array
   *   The settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroupingSettings($entityTypeId): array {
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $settings = [];

    // For user we count per role, everything else is counted per bundle.
    if ($entityTypeId === 'user') {
      $settings['baseTable'] = 'user__roles';
      $settings['bundleField'] = 'roles_target_id';
      $settings['groups'] = user_roles(TRUE);
      $settings['groupKey'] = 'roles';
    }
    else {
      $settings['baseTable'] = $entityTypeDefinition->getBaseTable();
      $settings['bundleField'] = $entityTypeDefinition->getKey('bundle');
      $settings['groups'] = $this->bundleInfo->getBundleInfo($entityTypeId);
      $settings['groupKey'] = 'bundles';
    }

    return $settings;
  }

}

<?php

namespace Drupal\sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\PermissionHandlerInterface;

/**
 * The Reporter class.
 *
 * @package Drupal\sampler
 */
class Reporter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldmanager;
  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The report.
   *
   * @var array
   */
  protected $report;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity types whose usage will get counted per bundle.
   *
   * @var array
   */
  protected $bundledEntityTypes = [
    'node',
    'taxonomy_term',
    'media',
    'comment',
    'paragraph',
    'block_content',
    'user',
  ];

  /**
   * Reporter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The Permission handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, EntityFieldManagerInterface $entity_field_manager, PermissionHandlerInterface $permission_handler, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->entityFieldmanager = $entity_field_manager;
    $this->permissionHandler = $permission_handler;
    $this->connection = $connection;
  }

  /**
   * Collect the data.
   *
   * @return $this
   */
  public function collect(): Reporter {
    $report = $this->entityData();

    $report['histogram'] = array_merge_recursive(
      $this->revisionHistogram(),
      $this->paragraphHistogram()
    );

    $this->report = $report;

    return $this;
  }

  /**
   * Print the report.
   *
   * @param null|string $filename
   *   The file to put the report into.
   */
  public function output($filename = NULL) {
    $report = $this->getFormattedReport();
    if ($filename) {
      file_put_contents($filename, $report);
    }
    else {
      print $report;
    }
  }

  /**
   * Format the report.
   *
   * @return string
   *   The formatted report.
   */
  protected function getFormattedReport(): string {
    return json_encode($this->report, JSON_PRETTY_PRINT);
  }

  /**
   * Retrieve counts per entity.
   */
  protected function entityData(): array {
    $structure = [];
    foreach ($this->bundledEntityTypes as $entityTypeId) {
      if (!$this->entityTypeManager->hasDefinition($entityTypeId)) {
        continue;
      }

      $settings = $this->getGroupingSettings($entityTypeId);

      $baseFields = array_keys($this->entityFieldmanager->getBaseFieldDefinitions($entityTypeId));
      $entityData = [];
      $entityData['base_fields'] = count($baseFields);

      foreach (array_keys($settings['groups']) as $group) {
        $fields = array_diff_key(
          array_keys($this->entityFieldmanager->getFieldDefinitions($entityTypeId, $group)),
          array_keys($baseFields)
        );
        $query = $this->connection->select($settings['baseTable'], 'b');
        $query->condition($settings['bundleField'], $group);
        $entityData[$settings['groupKey']][$group]['instances'] = $query->countQuery()->execute()->fetchField();

        if ($entityTypeId !== 'user') {
          $entityData[$settings['groupKey']][$group]['fields'] = count($fields);
        }
      }

      if ($entityTypeId === 'user') {
        $roleCounts = $entityData[$settings['groupKey']];
        $entityData['editing_users'] = $this->countEditingUsers($roleCounts);
      }

      $structure[$entityTypeId] = $entityData;
    }

    return $structure;
  }

  /**
   * Create a histogram of entities and their revisions.
   *
   * @return array
   *   The histogram.
   */
  protected function revisionHistogram(): array {
    $histogram = [];

    foreach ($this->bundledEntityTypes as $entityTypeId) {
      if (!$this->entityTypeManager->hasDefinition($entityTypeId)) {
        continue;
      }

      $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
      if (!$entityTypeDefinition->isRevisionable()) {
        continue;
      }

      $histogram[$entityTypeId] = ['revision' => []];
      $revisionTable = $entityTypeDefinition->getRevisionTable();
      $idKey = $entityTypeDefinition->getKey('id');
      $revisionId = $entityTypeDefinition->getKey('revision');

      $query = $this->connection->select($revisionTable, 'r');
      $query->addExpression('count(' . $revisionId . ')', 'count');
      $query->groupBy($idKey);

      $results = $query->execute();
      foreach ($results as $record) {
        if (!isset($histogram[$entityTypeId]['revision'][$record->count])) {
          $histogram[$entityTypeId]['revision'][$record->count] = 1;
          continue;
        }
        $histogram[$entityTypeId]['revision'][$record->count]++;
      }

      foreach ($histogram as $entityTypeId => $counts) {
        ksort($histogram[$entityTypeId]['revision']);
      }
    }

    return $histogram;
  }

  /**
   * Create a histogram of entities and their paragraphs.
   *
   * @return array
   *   The histogram.
   */
  protected function paragraphHistogram(): array {
    $histogram = [];

    $entityTypeId = 'paragraph';
    if (!$this->entityTypeManager->hasDefinition($entityTypeId)) {
      return $histogram;
    }

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $dataTable = $entityTypeDefinition->getDataTable();

    $query = $this->connection
      ->select($dataTable, 'r')
      ->fields('r', ['parent_type']);

    $query->isNotNull('parent_type');
    $query->addExpression('count(id)', 'count');
    $query->groupBy('parent_type');
    $query->groupBy('parent_id');

    $results = $query->execute();
    foreach ($results as $record) {
      if (!isset($histogram[$record->parent_type])) {
        $histogram[$record->parent_type] = ['paragraph' => []];
      }
      if (!isset($histogram[$record->parent_type]['paragraph'][$record->count])) {
        $histogram[$record->parent_type]['paragraph'][$record->count] = 1;
        continue;
      }
      $histogram[$record->parent_type]['paragraph'][$record->count]++;
    }

    foreach ($histogram as $parentType => $counts) {
      ksort($histogram[$parentType]['paragraph']);
    }

    return $histogram;
  }

  /**
   * Get roles that can create, modify or delete things.
   *
   * @param string $provider
   *   The permission provider.
   *
   * @return array
   *   The roles.
   */
  protected function getEditorRoles($provider): array {
    // Filter all permissions, that allow changing of node content.
    $permissions = array_filter($this->permissionHandler->getPermissions(), function ($permission, $permissionName) use ($provider) {
      return ($permission['provider'] === $provider && preg_match("/^(create|delete|edit|revert)/", $permissionName));
    }, ARRAY_FILTER_USE_BOTH);

    // Find all roles, that have these permissions.
    $roles = [];
    foreach ($permissions as $permissionName => $permission) {
      $roles += user_roles(FALSE, $permissionName);
    }

    return array_keys($roles);
  }

  /**
   * Get some settings for grouping entities.
   *
   * @param string $entityTypeId
   *   The entity type to get settings for.
   *
   * @return array
   *   The settings.
   */
  protected function getGroupingSettings($entityTypeId): array {
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $settings = [];
    // For user we count per role, everything else is counted per bundle.
    if ($entityTypeId === 'user') {
      $settings['baseTable'] = 'user__roles';
      $settings['bundleField'] = 'roles_target_id';
      $settings['groups'] = user_roles();
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

  /**
   * Count users, that can edit stuff.
   *
   * @param array $roleCounts
   *   Known counts of users in roles.
   *
   * @return array
   *   The number of editing users per provider.
   */
  protected function countEditingUsers(array $roleCounts): array {
    $provider = ['node', 'taxonomy'];
    $editingUsers = [];
    foreach ($provider as $p) {
      $editingUsers[$p] = ['instances' => 0];
      $editorRoles = $this->getEditorRoles($p);
      foreach ($editorRoles as $editorRole) {
        $editingUsers[$p]['instances'] += $roleCounts[$editorRole]['instances'];
      }
    }
    return $editingUsers;
  }

}

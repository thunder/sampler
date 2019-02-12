<?php

namespace Drupal\sampler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\PermissionHandlerInterface;

class Reporter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The report.
   */
  protected $report;

  /**
   * Entity types whose usage will get counted per bundle.
   *
   * @var array
   */
  protected $bundledEntityTypes = [
    'node',
    'taxonomy_term',
    'media',
    'paragraph',
    'comment'
  ];

  public function __construct(EntityTypeManagerInterface $entity_type_manager, PermissionHandlerInterface $permission_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->permissionHandler = $permission_handler;
  }

  public function collect() {
    $report = $this->countEntitiesPerBundle();
    $report += ['user' => $this->countUsers()];
    $this->report = $report;

    return $this;
  }

  public function output($filename = NULL) {
    $report = $this->getFormattedReport();
    if ($filename) {
      file_put_contents($filename, $report);
    }
    else {
      print $report;
    }
  }

  protected function getFormattedReport(): string {
    return json_encode($this->report, JSON_PRETTY_PRINT);
  }

  /**
   * Count Entities by Entity type and bundle.
   *
   * The entity types are defined in $this->bundledEntityTypes.
   *
   * @return array
   *  The entity count. Keyed by entity_type and bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function countEntitiesPerBundle() {
    $results = [];

    foreach ($this->bundledEntityTypes as $entity_type) {
      // Entity not not exist in this installation? ignore it.
      if (!$this->entityTypeManager->hasDefinition($entity_type)){
        continue;
      }

      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      $results[$entity_type][$entity_type_definition->getKey('bundle')] =
        $this->countGroupedInstances(
          $entity_type,
          $entity_type_definition->getKey('bundle'),
          $entity_type_definition->getKey('id')
        );

    }

    return $results;
  }

  /**
   * Count users per role, and count editors.
   *
   * For this we call "editors" all users, that are allowed to create, modify
   * or delete content. This might not only be users with the role "editor"
   *
   * @return array
   *  Return an array, that is keyed by role and has the grouped count as value.
   *  An additional key "editors" is added, that provides the count of editors.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function countUsers() {
    $entity_type = 'user';
    $group_key = 'roles';
    $results = ['role' => [], 'editors' => 0];

    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
    $id_key = $entity_type_definition->getKey('id');

    $results['role'] = $this->countGroupedInstances($entity_type, $group_key, $id_key);

    $editor_roles = $this->getEditorRoles();

    foreach($editor_roles as $editor_role) {
      $results['editors'] += $results['role'][$editor_role];
    }

    return $results;
  }

  /**
   * Get roles that can create, modify or delete nodes.
   *
   * @return array
   *  The roles.
   */
  protected function getEditorRoles() {
    // Filter all permissions, that allow changing of node content.
    $permissions = array_filter($this->permissionHandler->getPermissions(), function ($permission, $permission_name) {
      return ($permission['provider'] === "node" && preg_match("/^(create|delete|edit|revert)/", $permission_name));
    }, ARRAY_FILTER_USE_BOTH);

    // Find all roles, that have these permissions.
    $roles = [];
    foreach ($permissions as $permission_name => $permission) {
      $roles += user_roles(FALSE, $permission_name);
    }

    return array_keys($roles);
  }

  protected function countGroupedInstances($entity_type, $group_key, $id_key) {
    $entity_type_storage = $this->entityTypeManager->getStorage($entity_type);
    $entity_aggregate_query = $entity_type_storage->getAggregateQuery();

    $count_alias = 'count';
    $counts = [];

    $result = $entity_aggregate_query
      ->groupBy($group_key)
      ->aggregate($id_key, 'COUNT', NULL, $count_alias)
      ->execute();

    foreach ($result as $item) {
      $first_key = key($item);
      if (!empty($item[$first_key])) {
        $counts[$item[$first_key]] = $item[$count_alias];
      }
    }

    return $counts;
  }

}

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
    $data = [];
    $baseFields = array_keys($this->entityFieldManager->getBaseFieldDefinitions($entityTypeId));
    $settings = $this->getGroupingSettings($entityTypeId);

    foreach (array_keys($settings['groups']) as $group) {
      $mapping = $this->getGroupMapping($entityTypeId, $group);

      $query = $this->connection->select($settings['baseTable'], 'b');
      $query->condition($settings['bundleField'], $group);
      $data[$mapping]['instances'] = (integer) $query->countQuery()->execute()->fetchField();

      if ($entityTypeId !== 'user') {
        $fields = array_diff_key(
          array_keys($this->entityFieldManager->getFieldDefinitions($entityTypeId, $group)),
          array_keys($baseFields)
        );
        $data[$mapping]['fields'] = count($fields);
      }
    }

    if ($entityTypeId === 'user') {
      $data[$entityTypeId]['editing_users'] = $this->countEditingUsers($data);
    }

    return $data;
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
      $roles += user_roles(TRUE, $permissionName);
    }

    return array_keys($roles);
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
        $editingUsers[$p]['instances'] += $roleCounts[$this->getGroupMapping('user', $editorRole)]['instances'];
      }
    }

    return $editingUsers;
  }

  /**
   * Map group names to an integer value.
   *
   * The mapped integer will be used in output instead of the group name.
   *
   * @param string $entityTypeId
   *   The grouped entity.
   * @param string $group
   *   The group to map.
   * @param int $mapping
   *   The id to use as mapping, must be an integer and unique.
   *
   * @throws \InvalidArgumentException
   */
  protected function setGroupMapping(string $entityTypeId, string $group, int $mapping) {
    if (!isset($this->groupMapping[$entityTypeId])) {
      $this->groupMapping[$entityTypeId] = [];
    }
    if (in_array($mapping, $this->groupMapping[$entityTypeId])) {
      throw new \InvalidArgumentException('Mapping already exists');
    }

    $this->groupMapping[$group] = $mapping;
  }

  /**
   * Get mapped value of a group.
   *
   * @param string $entityTypeId
   *   The grouped entity.
   * @param string $group
   *   The group to map.
   *
   * @return string
   *   The mapped value.
   */
  protected function getGroupMapping($entityTypeId, $group): string {
    if ($this->anonymize === FALSE) {
      return $group;
    }

    if (!isset($this->groupMapping[$entityTypeId])) {
      $this->groupMapping[$entityTypeId] = [$group => 'group-0'];
      return $this->groupMapping[$entityTypeId][$group];
    }

    if (!isset($this->groupMapping[$entityTypeId][$group])) {
      $last = end($this->groupMapping[$entityTypeId]);
      $this->groupMapping[$entityTypeId][$group] = ++$last;
    }

    return $this->groupMapping[$entityTypeId][$group];
  }

  /**
   * {@inheritdoc}
   */
  public function key($entityTypeId): string {
    return $this->getGroupingSettings($entityTypeId)['groupKey'];
  }

}

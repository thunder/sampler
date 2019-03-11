<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\sampler\SamplerBase;
use Drupal\sampler\Traits\GroupedDataTrait;

/**
 * Collects user data.
 *
 * @Sampler(
 *   id = "user",
 *   label = @Translation("User"),
 *   description = @Translation("Collects user data.")
 * )
 */
class User extends SamplerBase {

  use GroupedDataTrait;

  /**
   * {@inheritdoc}
   */
  public function collect($entityTypeId) {
    $data = [];

    $roles = array_keys(user_roles(TRUE));

    $nodeEditingRoles = $this->getEditorRoles('node');
    $taxonomyEditingRoles = $this->getEditorRoles('taxonomy');

    foreach ($roles as $role) {
      $mapping = $this->getGroupMapping($entityTypeId, $role);

      $query = $this->connection->select('user__roles', 'b');
      $query->condition('roles_target_id', $role);
      $data[$mapping]['instances'] = (integer) $query->countQuery()->execute()->fetchField();

      $data[$mapping]['is_node_editing'] = in_array($mapping, $nodeEditingRoles);
      $data[$mapping]['is_taxonomy_editing'] = in_array($mapping, $taxonomyEditingRoles);
    }

    return $data;
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
  protected function getEditorRoles($provider) {
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
   * {@inheritdoc}
   */
  public function isApplicable($entityTypeId) {
    return $entityTypeId === 'user';
  }

  /**
   * {@inheritdoc}
   */
  public function key($entityTypeId): string {
    return 'role';
  }

}

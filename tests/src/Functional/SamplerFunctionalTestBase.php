<?php

namespace Drupal\Tests\sampler\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Base class for Sampler functional tests.
 */
abstract class SamplerFunctionalTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field',
    'media',
    'sampler',
    'sampler_test',
  ];

  /**
   * Create a given amount of users for a certain role.
   *
   * @param int $rid
   *   The role id the users should have.
   * @param int $number
   *   The number of users to create.
   */
  protected function createUsersForRole($rid, $number = 1) {
    for ($i = 0; $i < $number; $i++) {
      $edit = [];
      $edit['name'] = !empty($name) ? $name : $this->randomMachineName();
      $edit['mail'] = $edit['name'] . '@example.com';
      $edit['pass'] = user_password();
      $edit['status'] = 1;
      $edit['roles'] = [$rid];

      $account = User::create($edit);
      $account->save();
    }
  }

}

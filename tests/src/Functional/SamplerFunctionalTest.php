<?php

namespace Drupal\Tests\sampler\Functional;

class SamplerFunctionalTest extends SamplerFunctionalTestBase {

  /**
   * Test sampling of data.
   */
  public function testUserDataSampling() {
    $nodeEditingRid1 = 'editor1';
    $nodeEditingRid2 = 'editor2';
    $nonEditingRid = 'restricted';

    $numberOfNodeEditors1 = 4;
    $numberOfNodeEditors2 = 5;
    $numberOfRestricted = 6;

    $this->createRole(['create type_one content'], $nodeEditingRid1);
    $this->createRole(['create type_two content'], $nodeEditingRid2);
    $this->createRole([], $nonEditingRid);

    $this->createUsersForRole($nodeEditingRid1, $numberOfNodeEditors1);
    $this->createUsersForRole($nodeEditingRid2, $numberOfNodeEditors2);
    $this->createUsersForRole($nonEditingRid, $numberOfRestricted);

    $report = $this->container->get('sampler.reporter')
      ->setAnonymize(FALSE)
      ->collect()
      ->getReport();
    file_put_contents('/tmp/debug-test.txt', json_encode($report, JSON_PRETTY_PRINT));

    $userReport = $report['user'];

    $this->assertEquals(17, $userReport['base_fields']);
    $this->assertEquals($numberOfNodeEditors1, $userReport['roles'][$nodeEditingRid1]['instances']);
    $this->assertEquals($numberOfNodeEditors2, $userReport['roles'][$nodeEditingRid2]['instances']);
    $this->assertEquals($numberOfRestricted, $userReport['roles'][$nonEditingRid]['instances']);

    $this->assertEquals(($numberOfNodeEditors1 + $numberOfNodeEditors2), $userReport['editing_users']['node']['instances']);

    // Force fail while writing test, remove when finished with PR.
    $this->assertTrue(FALSE);
  }

}

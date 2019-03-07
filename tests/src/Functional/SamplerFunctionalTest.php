<?php

namespace Drupal\Tests\sampler\Functional;

/**
 * Tests the sampler module.
 *
 * @group sampler
 */
class SamplerFunctionalTest extends SamplerFunctionalTestBase {

  /**
   * Test sampling of user data.
   */
  public function testUserDataSampling() {
    $nonEditingRid = 'restricted';
    $nodeEditingRid1 = 'node_editor_1';
    $nodeEditingRid2 = 'node_editor_2';
    $taxonomyEditingRid1 = 'term_editor_1';

    $numberOfRestricted = 1;
    $numberOfNodeEditors1 = 2;
    $numberOfNodeEditors2 = 3;
    $numberOfTaxonomyEditors1 = 4;

    $this->createRole([], $nonEditingRid);
    $this->createRole(['create type_one content'], $nodeEditingRid1);
    $this->createRole(['create type_two content'], $nodeEditingRid2);
    $this->createRole(['edit terms in vocabulary_one'], $taxonomyEditingRid1);

    $this->createUsersWithRole($nonEditingRid, $numberOfRestricted);
    $this->createUsersWithRole($nodeEditingRid1, $numberOfNodeEditors1);
    $this->createUsersWithRole($nodeEditingRid2, $numberOfNodeEditors2);
    $this->createUsersWithRole($taxonomyEditingRid1, $numberOfTaxonomyEditors1);

    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $userReport = $report['user'];

    $this->assertEquals(17, $userReport['base_fields']);
    $this->assertEquals($numberOfRestricted, $userReport['roles'][$nonEditingRid]['instances']);
    $this->assertEquals($numberOfNodeEditors1, $userReport['roles'][$nodeEditingRid1]['instances']);
    $this->assertEquals($numberOfNodeEditors2, $userReport['roles'][$nodeEditingRid2]['instances']);
    $this->assertEquals($numberOfTaxonomyEditors1, $userReport['roles'][$taxonomyEditingRid1]['instances']);

    $this->assertEquals(($numberOfNodeEditors1 + $numberOfNodeEditors2), $userReport['editing_users']['node']['instances']);
    $this->assertEquals($numberOfTaxonomyEditors1, $userReport['editing_users']['taxonomy']['instances']);
  }

  /**
   * Test sampling of node data.
   */
  public function testNodeDataSampling() {
    $nodeTypeOne = 'type_one';
    $nodeTypeTwo = 'type_two';

    $numberOfNodesTypeOne = 2;
    $numberOfNodesTypeTwo = 3;

    $this->createNodesOfType($nodeTypeOne, $numberOfNodesTypeOne, 1);
    $this->createNodesOfType($nodeTypeTwo, $numberOfNodesTypeTwo, 1);

    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $nodeReport = $report['node'];

    $this->assertEquals(18, $nodeReport['base_fields']);
    $this->assertEquals($numberOfNodesTypeOne, $nodeReport['bundles'][$nodeTypeOne]['instances']);
    $this->assertEquals($numberOfNodesTypeTwo, $nodeReport['bundles'][$nodeTypeTwo]['instances']);

    $this->assertEquals(2, $nodeReport['bundles'][$nodeTypeOne]['fields']);
    $this->assertEquals(0, $nodeReport['bundles'][$nodeTypeTwo]['fields']);
  }

  /**
   * Test sampling of data for revision histograms.
   */
  public function testRevisionHistogramDataSampling() {
    $nodeTypeOne = 'type_one';

    $numberOfNodesWithOneRevisions = 3;
    $numberOfNodesWithTwoRevisions = 2;
    $numberOfNodesWithThreeRevisions = 1;

    $this->createNodesOfType($nodeTypeOne, $numberOfNodesWithOneRevisions, 1);
    $this->createNodesOfType($nodeTypeOne, $numberOfNodesWithTwoRevisions, 2);
    $this->createNodesOfType($nodeTypeOne, $numberOfNodesWithThreeRevisions, 3);

    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $histogramReport = $report['node']['histogram'];

    $this->assertEquals($numberOfNodesWithOneRevisions, $histogramReport['revision'][1]);
    $this->assertEquals($numberOfNodesWithTwoRevisions, $histogramReport['revision'][2]);
    $this->assertEquals($numberOfNodesWithThreeRevisions, $histogramReport['revision'][3]);
  }

}

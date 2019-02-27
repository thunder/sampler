<?php

namespace Drupal\Tests\sampler\Functional;

class SamplerFunctionalTest extends SamplerFunctionalTestBase {

  /**
   * Test sampling of data.
   */
  public function testDataSampling() {
    $editor = $this->drupalCreateUser(['create one content']);
    $report = $this->container->get('sampler.reporter')->collect()->getReport();

    file_put_contents('/tmp/debug-test.txt', json_encode($report, JSON_PRETTY_PRINT));

    $node_report = $report['node'];
    $bundle = 'group-0';

    $this->assertEquals(18, $node_report['base_fields']);
    $this->assertEquals(2, $node_report['bundles'][$bundle]['fields']);

    // Force fail while writing test, remove when finished with PR.
    $this->assertTrue(FALSE);
  }

}

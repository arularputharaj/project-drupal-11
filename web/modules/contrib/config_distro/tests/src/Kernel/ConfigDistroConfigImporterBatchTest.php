<?php

declare(strict_types=1);

namespace Drupal\Tests\config_distro\Kernel;

use Drupal\config_distro\ConfigDistroConfigImporterBatch;
use Drupal\config_distro\Event\ConfigDistroEvents;
use Drupal\config_distro\Event\DistroStorageImportEvent;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Kernel tests for ConfigDistroConfigImporterBatch.
 */
#[Group('config_distro')]
#[RunTestsInSeparateProcesses]
class ConfigDistroConfigImporterBatchTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_distro'];

  /**
   * Tests the function that alters the configuration distribution batch.
   */
  public function testConfigDistroBatchAlter() {
    // Create a batch array with initial values.
    $batch = [
      'form_state' => new FormState(),
      'sets' => [
        0 => [
          'finished' => [
            0 => 'Drupal\Core\Config\Importer\ConfigImporterBatch',
          ],
        ],
      ],
    ];
    // Set a value for the 'form_id' in the form state of the batch.
    $batch['form_state']->setValue('form_id', 'config_distro_import_form');
    // Call the function that alters the batch.
    config_distro_batch_alter($batch);
    // Check that the 'finished' key of the batch has been correctly altered.
    $this->assertEquals('Drupal\config_distro\ConfigDistroConfigImporterBatch', $batch['sets'][0]['finished'][0]);
  }

  /**
   * Tests the finish method when an update is available.
   */
  public function testFinishWithConfigUpdate() {
    // Mock the EventDispatcher.
    $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
    // Expect an instance of DistroStorageImportEvent as the first argument
    // and ConfigDistroEvents::IMPORT as the second argument.
    $mockEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
          $this->isInstanceOf(DistroStorageImportEvent::class),
          $this->equalTo(ConfigDistroEvents::IMPORT)
      );
    // Inject the mock into the container.
    $this->container->set('event_dispatcher', $mockEventDispatcher);
    // Simulate the update process.
    ConfigDistroConfigImporterBatch::finish(TRUE, [], []);
  }

}

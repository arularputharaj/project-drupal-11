<?php

declare(strict_types=1);

namespace Drupal\Tests\config_distro\Kernel;

use Drupal\config_distro\Form\ConfigDistroImportForm;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for ConfigDistroImportForm.
 */
#[Group('config_distro')]
#[RunTestsInSeparateProcesses]
class ConfigDistroImportFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_distro', 'config'];

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->formBuilder = $this->container->get('form_builder');
  }

  /**
   * Tests that the form exists and is accessible.
   */
  public function testFormExists() {
    $form = $this->formBuilder->getForm(ConfigDistroImportForm::class);
    $this->assertIsArray($form);
    $this->assertArrayHasKey('#form_id', $form);
    $this->assertEquals('config_distro_import_form', $form['#form_id']);
  }

  /**
   * Tests that the form properly extends ConfigSync.
   */
  public function testFormExtendsConfigSync() {
    $form_object = ConfigDistroImportForm::create($this->container);
    $this->assertInstanceOf('Drupal\config\Form\ConfigSync', $form_object);
    $this->assertInstanceOf(ConfigDistroImportForm::class, $form_object);
  }

  /**
   * Tests that the form uses distro storage instead of sync storage.
   */
  public function testFormUsesDistroStorage() {
    $form_object = ConfigDistroImportForm::create($this->container);

    // Use reflection to access protected syncStorage property.
    $reflection = new \ReflectionClass($form_object);
    $sync_storage_property = $reflection->getProperty('syncStorage');
    $sync_storage_property->setAccessible(TRUE);
    $sync_storage = $sync_storage_property->getValue($form_object);

    // Verify the storage is the distro storage service.
    $distro_storage = $this->container->get('config_distro.storage.distro');
    $this->assertSame($distro_storage, $sync_storage);
  }

  /**
   * Tests that the form uses NullStorage for snapshot storage.
   */
  public function testFormUsesNullSnapshotStorage() {
    $form_object = ConfigDistroImportForm::create($this->container);

    // Use reflection to access protected snapshotStorage property.
    $reflection = new \ReflectionClass($form_object);
    $snapshot_storage_property = $reflection->getProperty('snapshotStorage');
    $snapshot_storage_property->setAccessible(TRUE);
    $snapshot_storage = $snapshot_storage_property->getValue($form_object);

    // Verify the snapshot storage is NullStorage.
    $this->assertInstanceOf(NullStorage::class, $snapshot_storage);
  }

  /**
   * Tests that the form ID is correct.
   */
  public function testFormId() {
    $form_object = ConfigDistroImportForm::create($this->container);
    $this->assertEquals('config_distro_import_form', $form_object->getFormId());
  }

  /**
   * Tests that the form can be built without errors.
   */
  public function testFormBuild() {
    $form_state = new FormState();
    $form = $this->formBuilder->getForm(ConfigDistroImportForm::class, $form_state);

    // Verify basic form structure exists.
    $this->assertIsArray($form);
    $this->assertArrayHasKey('#form_id', $form);
    $this->assertArrayHasKey('#build_id', $form);
    $this->assertArrayHasKey('actions', $form);
  }

}

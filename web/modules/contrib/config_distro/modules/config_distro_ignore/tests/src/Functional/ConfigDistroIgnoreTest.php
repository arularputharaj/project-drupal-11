<?php

declare(strict_types=1);

namespace Drupal\Tests\config_distro_ignore\Functional;

use Drupal\config_distro_ignore\Plugin\ConfigFilter\DistroIgnoreFilter;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for config_distro_ignore behavior during distro imports.
 */
#[Group('config_distro_ignore')]
#[RunTestsInSeparateProcesses]
class ConfigDistroIgnoreTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_distro_ignore',
    'config_distro_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to run distro sync and edit ignore settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'synchronize distro configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests non-ignored config is updated from distro storage.
   */
  public function testNonIgnoredConfigIsUpdatedDuringImport(): void {
    $this->setIgnoreSettings([], [], []);
    $this->setActiveSiteName('Harbor Site');

    $this->assertSame('Harbor Site Arrr', $this->getDistroSiteName());

    $this->importDistroConfigAndAssertExpectedChanges(expect_changes: TRUE);

    $this->assertSame('Harbor Site Arrr', $this->getActiveSiteName());
  }

  /**
   * Tests ignored config is retained during distro import.
   */
  public function testIgnoredConfigIsRetainedDuringImport(): void {
    $this->setActiveSiteName('Captain Site');

    // Pirate filter is active and creates an importable diff for system.site.
    $this->setIgnoreSettings([], [], []);
    $this->assertSame('Captain Site Arrr', $this->getDistroSiteName());

    $this->setIgnoreSettings(['system.site'], [], []);
    $this->assertSame('Captain Site', $this->getDistroSiteName());

    $this->importDistroConfigAndAssertExpectedChanges(expect_changes: FALSE);

    $this->assertSame('Captain Site', $this->getActiveSiteName());
  }

  /**
   * Tests wildcard ignore patterns retain matching config.
   */
  public function testWildcardIgnoredConfigIsRetainedDuringImport(): void {
    $this->setActiveSiteName('Wildcard Site');

    $this->setIgnoreSettings([], [], []);
    $this->assertSame('Wildcard Site Arrr', $this->getDistroSiteName());

    $this->setIgnoreSettings([], ['system.*'], []);
    $this->assertSame('Wildcard Site', $this->getDistroSiteName());

    $this->importDistroConfigAndAssertExpectedChanges(expect_changes: FALSE);

    $this->assertSame('Wildcard Site', $this->getActiveSiteName());
  }

  /**
   * Tests hash-based ignore retains config when hash matches distro data.
   */
  public function testHashMatchedIgnoredConfigIsRetainedDuringImport(): void {
    $this->setActiveSiteName('Hash Match Site');
    $this->setIgnoreSettings([], [], []);

    $distro_data = $this->container->get('config_distro.storage.distro')->read('system.site') ?? [];
    $hash = DistroIgnoreFilter::hashConfig($distro_data);

    $this->setIgnoreSettings([], ['system.site::' . $hash], []);
    $this->assertSame('Hash Match Site', $this->getDistroSiteName());

    $this->importDistroConfigAndAssertExpectedChanges(expect_changes: FALSE);

    $this->assertSame('Hash Match Site', $this->getActiveSiteName());
  }

  /**
   * Tests hash-based ignore does not retain config when hash is stale.
   */
  public function testHashMismatchedConfigIsUpdatedDuringImport(): void {
    $this->setActiveSiteName('Hash Mismatch Site');

    $this->setIgnoreSettings([], ['system.site::invalid_hash'], []);
    $this->assertSame('Hash Mismatch Site Arrr', $this->getDistroSiteName());

    $this->importDistroConfigAndAssertExpectedChanges(expect_changes: TRUE);

    $this->assertSame('Hash Mismatch Site Arrr', $this->getActiveSiteName());
  }

  /**
   * Updates config_distro_ignore.settings for the current test scenario.
   *
   * @param array $all_collections
   *   List of ignored names for all collections.
   * @param array $default_collection
   *   List of ignored names for default collection.
   * @param array $custom_collections
   *   List of ignored names for custom collections.
   */
  protected function setIgnoreSettings(array $all_collections, array $default_collection, array $custom_collections): void {
    $this->config('config_distro_ignore.settings')
      ->set('all_collections', $all_collections)
      ->set('default_collection', $default_collection)
      ->set('custom_collections', $custom_collections)
      ->save();

    // Rebuild container so services re-read filter configuration changes.
    $this->rebuildContainer();
  }

  /**
   * Sets the active site name.
   *
   * @param string $name
   *   Site name.
   */
  protected function setActiveSiteName(string $name): void {
    $this->config('system.site')
      ->set('name', $name)
      ->save();

    $this->container->get('config.factory')->reset('system.site');
  }

  /**
   * Returns the active site name.
   *
   * @return string
   *   Active site name.
   */
  protected function getActiveSiteName(): string {
    $this->container->get('config.factory')->reset('system.site');
    return (string) $this->config('system.site')->get('name');
  }

  /**
   * Returns the distro storage value for system.site name.
   *
   * @return string
   *   Distro storage site name.
   */
  protected function getDistroSiteName(): string {
    $data = $this->container->get('config_distro.storage.distro')->read('system.site') ?? [];
    return (string) ($data['name'] ?? '');
  }

  /**
   * Runs a distro import using the same importer class used by Drush/UI.
   */
  protected function importDistroConfigAndAssertExpectedChanges(bool $expect_changes): void {
    $storage_comparer = new StorageComparer(
      $this->container->get('config_distro.storage.distro'),
      $this->container->get('config.storage')
    );

    $storage_comparer->createChangelist();
    $has_changes = $storage_comparer->hasChanges();
    $this->assertSame($expect_changes, $has_changes, 'Unexpected distro change state before import.');
    if (!$has_changes) {
      return;
    }

    $config_importer = new ConfigImporter(
      $storage_comparer,
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme')
    );

    if ($config_importer->hasUnprocessedConfigurationChanges()) {
      $sync_steps = $config_importer->initialize();
      foreach ($sync_steps as $step) {
        $context = [];
        do {
          $config_importer->doSyncStep($step, $context);
        } while ($context['finished'] < 1);
      }
    }

    $this->assertSame([], $config_importer->getErrors());
    $this->container->get('cache.config')->deleteAll();
    $this->container->get('config.factory')->reset('system.site');
  }

}

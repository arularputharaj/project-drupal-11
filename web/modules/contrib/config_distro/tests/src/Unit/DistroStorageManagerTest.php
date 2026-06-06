<?php

declare(strict_types=1);

namespace Drupal\Tests\config_distro\Unit;

use Drupal\config_distro\DistroStorageManager;
use Drupal\Core\Config\ReadOnlyStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformerException;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Unit tests for DistroStorageManager.
 */
#[CoversClass(DistroStorageManager::class)]
#[Group('config_distro')]
class DistroStorageManagerTest extends UnitTestCase {

  /**
   * The mocked active storage.
   *
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $activeStorage;

  /**
   * The mocked memory storage.
   *
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $memoryStorage;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The mocked lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the active storage.
    $this->activeStorage = $this->getMockBuilder(StorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the memory storage.
    $this->memoryStorage = $this->getMockBuilder(StorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $mockedStorageCollection = $this->getMockBuilder(StorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $mockedStorageCollection->method('listAll')->willReturn([]);

    // Define methods for activeStorage.
    $this->activeStorage->method('getAllCollectionNames')->willReturn([]);
    $this->activeStorage->method('listAll')->willReturn([]);
    $this->activeStorage->method('createCollection')
      ->willReturn($mockedStorageCollection);

    // Define methods for memoryStorage.
    $this->memoryStorage->method('getAllCollectionNames')->willReturn([]);
    $this->memoryStorage->method('listAll')->willReturn([]);
    $this->memoryStorage->method('createCollection')
      ->willReturn($mockedStorageCollection);

    // Mock the event dispatcher.
    $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the lock backend.
    $this->lock = $this->getMockBuilder(LockBackendInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests the constructor.
   */
  public function testConstruct() {
    $manager = new DistroStorageManager(
          $this->activeStorage,
          $this->eventDispatcher,
          $this->lock
      );

    $this->assertInstanceOf(DistroStorageManager::class, $manager);
  }

  /**
   * Tests the getStorage method when lock is acquired immediately.
   */
  public function testGetStorageLockAcquiredImmediately() {
    // Configure the mock to return an array.
    $this->activeStorage->method('listAll')->willReturn([]);

    $this->lock->method('acquire')
      ->with(DistroStorageManager::LOCK_NAME)
      ->willReturn(TRUE);

    $manager = new DistroStorageManager(
        $this->activeStorage,
        $this->eventDispatcher,
        $this->lock
    );

    $result = $manager->getStorage();
    $this->assertInstanceOf(ReadOnlyStorage::class, $result);
  }

  /**
   * Tests the getStorage method when lock is acquired after waiting.
   */
  public function testGetStorageLockAcquiredAfterWaiting() {
    // Configure the mock to return an array.
    $this->activeStorage->method('listAll')->willReturn([]);

    $this->lock->method('acquire')
      ->with(DistroStorageManager::LOCK_NAME)
      ->willReturnOnConsecutiveCalls(FALSE, TRUE);

    $this->lock->expects($this->once())
      ->method('wait')
      ->with(DistroStorageManager::LOCK_NAME);

    $manager = new DistroStorageManager(
        $this->activeStorage,
        $this->eventDispatcher,
        $this->lock
    );

    $result = $manager->getStorage();
    $this->assertInstanceOf(ReadOnlyStorage::class, $result);
  }

  /**
   * Tests the getStorage method when lock is not acquired.
   */
  public function testGetStorageLockNotAcquired() {
    $this->lock->method('acquire')
      ->with(DistroStorageManager::LOCK_NAME)
      ->willReturnOnConsecutiveCalls(FALSE, FALSE);

    $this->lock->expects($this->once())
      ->method('wait')
      ->with(DistroStorageManager::LOCK_NAME);

    $manager = new DistroStorageManager(
          $this->activeStorage,
          $this->eventDispatcher,
          $this->lock
      );

    $this->expectException(StorageTransformerException::class);
    $this->expectExceptionMessage('Cannot acquire config distro transformer lock.');
    $manager->getStorage();
  }

}

<?php

namespace Drupal\Tests\migrate_scanner\Unit;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\migrate_scanner\Component\Discovery\YamlRecursiveDirectoryDiscovery;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * YamlRecursiveDirectoryDiscovery unit tests.
 *
 * @coversDefaultClass \Drupal\migrate_scanner\Component\Discovery\YamlRecursiveDirectoryDiscovery
 *
 * @group migrate_scanner
 */
class YamlRecursiveDirectoryDiscoveryTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');
  }

  /**
   * Tests YAML directory discovery.
   *
   * @covers ::findAll
   */
  public function testDiscovery() {
    vfsStream::setup('modules', NULL, [
      'test_1' => [
        'subdir1' => [
          'item_1.test.yml' => "id: item1\nname: 'test1 item 1'",
        ],
        'subdir2' => [
          'sub_subdir2' => [
            'item_2.test.yml' => "id: item2\nname: 'test1 item 2'",
          ],
        ],
        'subdir3' => [
          'sub_subdir3' => [
            'sub_sub_subdir3' => [
              'item_3.test.yml' => "id: item3\nname: 'test1 item 3'",
            ],
          ],
        ],
      ],
    ]);

    // Set up the directories to search.
    $directories = [
      // Multiple nested directories with valid items.
      'test_1' => [
        vfsStream::url('modules/test_1/subdir1'),
        vfsStream::url('modules/test_1/subdir2'),
        vfsStream::url('modules/test_1/subdir3'),
      ],
    ];

    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test');
    $data = $discovery->findAll();

    $this->assertSame([
      'id' => 'item1',
      'name' => 'test1 item 1',
      YamlRecursiveDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir1/item_1.test.yml',
    ], $data['test_1']['item1']);
    $this->assertSame([
      'id' => 'item2',
      'name' => 'test1 item 2',
      YamlRecursiveDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir2/sub_subdir2/item_2.test.yml',
    ], $data['test_1']['item2']);
    $this->assertSame([
      'id' => 'item3',
      'name' => 'test1 item 3',
      YamlRecursiveDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir3/sub_subdir3/sub_sub_subdir3/item_3.test.yml',
    ], $data['test_1']['item3']);

    $this->assertCount(3, $data['test_1']);
  }

  /**
   * Tests YAML directory discovery with provided patterns.
   *
   * @covers ::findAll
   */
  public function testDiscoveryExcludePattern() {
    vfsStream::setup('modules', NULL, [
      'test_1' => [
        'subdir1' => [
          'item_1.test.yml' => "id: item1\nname: 'test1 item 1'",
        ],
        'subdir2' => [
          'sub_subdir2' => [
            'item_2.test.yml' => "id: item2\nname: 'test1 item 2'",
            'item_3_excl.test.yml' => "id: item3\nname: 'test1 item 3'",
          ],
          'exclude' => [
            'item_4.test.yml' => "id: item4\nname: 'test1 item 4'",
          ],
        ],
      ],
    ]);

    // Set up the directories to search.
    $directories = [
      'test_1' => [
        vfsStream::url('modules/test_1/subdir1'),
        vfsStream::url('modules/test_1/subdir2'),
      ],
    ];

    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test');
    $data = $discovery->findAll();
    $this->assertCount(4, $data['test_1']);

    // Exclude the directory.
    $patterns = ['exclude' => ['/exclude/']];
    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test', 'id', $patterns);
    $data = $discovery->findAll();
    $this->assertCount(3, $data['test_1']);
    $this->assertArrayNotHasKey('item4', $data['test_1']);

    // Exclude the file.
    $patterns = ['exclude' => ['/_excl/']];
    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test', 'id', $patterns);
    $data = $discovery->findAll();
    $this->assertCount(3, $data['test_1']);
    $this->assertArrayNotHasKey('item3', $data['test_1']);

    // Exclude by multiple patterns.
    $patterns = ['exclude' => ['/_excl/', '/exclude/']];
    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test', 'id', $patterns);
    $data = $discovery->findAll();
    $this->assertCount(2, $data['test_1']);
    $this->assertArrayNotHasKey('item3', $data['test_1']);
    $this->assertArrayNotHasKey('item4', $data['test_1']);

    // Include the directory.
    $patterns = ['include' => ['/exclude/']];
    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test', 'id', $patterns);
    $data = $discovery->findAll();
    $this->assertCount(1, $data['test_1']);
    $this->assertArrayHasKey('item4', $data['test_1']);

    // Include by multiple patterns.
    $patterns = ['include' => ['/exclude/', '/subdir1/']];
    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test', 'id', $patterns);
    $data = $discovery->findAll();
    $this->assertCount(2, $data['test_1']);
    $this->assertArrayHasKey('item1', $data['test_1']);
    $this->assertArrayHasKey('item4', $data['test_1']);

    // Include the directory but exclude some files.
    $patterns = [
      'include' => ['/subdir2/'],
      'exclude' => ['/_excl/'],
    ];
    $discovery = new YamlRecursiveDirectoryDiscovery($directories, 'test', 'id', $patterns);
    $data = $discovery->findAll();
    $this->assertCount(2, $data['test_1']);
    $this->assertArrayHasKey('item2', $data['test_1']);
    $this->assertArrayHasKey('item4', $data['test_1']);
  }

}

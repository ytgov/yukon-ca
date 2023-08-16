<?php

require_once '../geoPHP.inc';
require_once 'PHPUnit/Autoload.php';
/**
 *
 */
class GeosTests extends \PHPUnit\Framework\TestCase {

  /**
   *
   */
  public function setUp(): void {

  }

  /**
   *
   */
  public function testGeos() {
    foreach (scandir('./input') as $file) {
      $parts = explode('.', $file);
      if ($parts[0]) {
        $format = $parts[1];
        $value = file_get_contents('./input/' . $file);
        $geometry = geoPHP::load($value, $format);

        $geosMethods = [
          ['name' => 'geos'],
          ['name' => 'setGeos', 'argument' => $geometry->geos()],
          ['name' => 'PointOnSurface'],
          ['name' => 'equals', 'argument' => $geometry],
          ['name' => 'equalsExact', 'argument' => $geometry],
          ['name' => 'relate', 'argument' => $geometry],
          ['name' => 'checkValidity'],
          ['name' => 'isSimple'],
          ['name' => 'buffer', 'argument' => '10'],
          ['name' => 'intersection', 'argument' => $geometry],
          ['name' => 'convexHull'],
          ['name' => 'difference', 'argument' => $geometry],
          ['name' => 'symDifference', 'argument' => $geometry],
          ['name' => 'union', 'argument' => $geometry],
          ['name' => 'simplify', 'argument' => '0'],
          ['name' => 'disjoint', 'argument' => $geometry],
          ['name' => 'touches', 'argument' => $geometry],
          ['name' => 'intersects', 'argument' => $geometry],
          ['name' => 'crosses', 'argument' => $geometry],
          ['name' => 'within', 'argument' => $geometry],
          ['name' => 'contains', 'argument' => $geometry],
          ['name' => 'overlaps', 'argument' => $geometry],
          ['name' => 'covers', 'argument' => $geometry],
          ['name' => 'coveredBy', 'argument' => $geometry],
          ['name' => 'distance', 'argument' => $geometry],
          ['name' => 'hausdorffDistance', 'argument' => $geometry],
        ];

        foreach ($geosMethods as $method) {
          $argument = NULL;
          $method_name = $method['name'];
          if (isset($method['argument'])) {
            $argument = $method['argument'];
          }

          switch ($method_name) {
            case 'isSimple':
            case 'equals':
            case 'geos':
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
              }
              break;

            default:
              if ($geometry->geometryType() == 'Point') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
              }
              if ($geometry->geometryType() == 'LineString') {
                $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
              }
              if ($geometry->geometryType() == 'MultiLineString') {
                $this->assertNull($geometry->$method_name($argument), 'Failed on ' . $method_name);
              }
          }
        }

      }
    }
  }

}

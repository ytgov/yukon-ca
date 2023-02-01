<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Gets author.
 *
 * Example:
 *
 * @code
 * process:
 *   uid:
 *     plugin: kellett_author
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "kellett_author",
 * )
 */
class Author extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $uid = $node['node_uid'] ?? 1;
    if (!empty($uid)) {
      $connection = Database::getConnection('default', 'migrate');
      $query = $connection->select('users', 'u')
        ->fields('u', [
          'uid',
          'name',
          'mail',
          'language',
        ]);
      $query->innerJoin('users_roles', 'ur', 'ur.uid = u.uid');
      $query->fields('ur',
        ['rid']);
      $query->innerJoin('role', 'r', 'r.rid = ur.rid');
      $query->fields('r',
        ['name',
          'weight',
        ]);
      $query->condition('u.uid', $uid);

      $result = $query->execute()->fetchObject();

      if ($result) {
        $id = str_replace(' ', '_', strtolower($result->r_name));
        $id = str_replace('+', '', $id);
        $id = str_replace('-', '_', $id);
        $role = \Drupal::service('entity_type.manager')
          ->getStorage('user_role')->loadByProperties(['id' => $id]);
        $role = reset($role);
        if (!$role) {
          if ($result->r_name && is_string($result->r_name)) {
            $role = Role::create([
              'id' => $id,
              'name' => $result->r_name,
              'weight' => $result->weight,
            ]);
            $role->save();
          }
        }

        $user = \Drupal::service('entity_type.manager')
          ->getStorage('user')->loadByProperties(['name' => $result->name]);
        $user = reset($user);
        if (!$user) {
          $user = User::create([
            'name' => $result->name,
            'mail' => $result->mail,
            'langcode' => $result->language,
            'roles' => [$role->id()],
            'status' => 1,
          ]);
          $user->save();
        }

        return $user->id();
      }
    }
  }

}

<?php

namespace Drupal\attendance\Model;

class BaseModel
{
  protected string $table_name;
  private $db;

  public function __construct()
  {
    $this->db = \Drupal::database();
  }

  /**
   * データベース接続を取得。
   */
  protected function getDatabase()
  {
    return $this->db;
  }

  /**
   * ロガーを取得。
   */
  protected function getLogger()
  {
    $class = get_called_class();
    $parts = explode('\\', $class);
    $module = $parts[1]; // モジュール名を取得
    return \Drupal::logger($module);
  }

  /**
   * エンティティをデータベースに保存。
   */
  public function insert(array $data)
  {
    try {
      return $this->getDatabase()->insert($this->table_name)
        ->fields($data)
        ->execute();
    } catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return false;
    }
  }

  /**
   * エンティティを更新。
   */
  public function update(int $id, array $data)
  {
    try {
      $this->getDatabase()->update($this->table_name)
        ->fields($data)
        ->condition('id', $id)
        ->execute();
      return true;
    } catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return false;
    }
  }



  /**
   * IDでエンティティを削除。
   */
  public function delete(int $id)
  {
    try {
      $this->getDatabase()->delete($this->table_name)
        ->condition('id', $id)
        ->execute();
      return true;
    } catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return false;
    }
  }

  /**
   * IDでエンティティを読み込む。
   */
  public function find(int $id)
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t')
        ->condition('id', $id)
        ->execute();

      return $query->fetchAssoc();
    } catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return null;
    }
  }

  /**
   * すべてのエンティティを取得。
   */
  public function findAll()
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t')
        ->execute();

      return $query->fetchAll();
    } catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return [];
    }
  }

  /**
   * カスタムクエリでエンティティを検索。
   */
  public function findBy(array $conditions, array $options = [])
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t');

      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }

      if (isset($options['order_by'])) {
        foreach ($options['order_by'] as $field => $direction) {
          $query->orderBy($field, $direction);
        }
      }

      if (isset($options['limit'])) {
        $query->range(0, $options['limit']);
      }

      return $query->execute()->fetchAll();
    } catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return [];
    }
  }

   /**
   * カスタムクエリでエンティティを1件取得。
   */
  public function findOneBy(array $conditions)
  {
    try {
      $query = $this->getDatabase()->select($this->table_name, 't')
        ->fields('t');

      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }

      return $query->execute()->fetchAssoc();
    } catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return null;
    }
  }

}

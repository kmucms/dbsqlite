<?php

namespace kmucms\dbsqlite\auxiliaryPrivate;

class DbStructureSqlite{

  private $parDbFile = '';
  private $db;

  public function __construct(string $dbFile){
    if(!is_file($dbFile)){
      $dir = dirname($dbFile);
      if(!is_dir($dir)){
        mkdir($dir . '/', 0777, TRUE);
      }
      file_put_contents($dbFile, '');
    }
    $this->parDbFile = $dbFile;
    $this->db        = new \SQLite3($dbFile);
  }

// <editor-fold defaultstate="expanded" desc="structure modification">

  public function deleteAll(){
    $tables = $this->getTableNames();
    foreach($tables as $table){
      $this->deleteTable($table);
    }
  }

  public function addTable($name){
    if($this->isTable($name)){
      return;
    }
    $sql = "CREATE TABLE $name (id  INTEGER PRIMARY KEY)";
    $this->doExecute($sql);
  }

  public function isTable($name){
    return in_array($name, $this->getTableNames());
  }

  public function renameTable($nameOld, $nameNew){
    $this->doExecute("alter table $nameOld rename to $nameNew");
  }

  public function setTableName($nameOld, $nameNew){
    $this->renameTable($nameOld, $nameNew);
  }

  public function removeTable($name){
    $this->doExecute("drop table " . $name);
  }

  public function isColumn($tableName, $columnName){
    return in_array($columnName, $this->getColumnNames($tableName));
  }

  public function addColumn($table, $column, $type = 'string', $defaultValue = null){
    if(in_array($column, $this->getColumnNames($table))){
      if($this->getColumnType($table, $column) !== $type){
        $this->setColumnType($table, $column, $type);
      }
      if($this->getColumnDefault($table, $column) !== $defaultValue && $defaultValue !== null){
        $this->setColumnDefault($table, $column, $defaultValue);
      }
      return;
    }
    $defaultValue = $defaultValue === null ? '' : " DEFAULT '" . $defaultValue . "'";
    $sql          = "ALTER TABLE $table ADD $column $type $defaultValue;";
    $this->doExecute($sql);
  }

  public function renameColumn($table, $columnOld, $columnNew){
    $this->doExecute("alter table $table rename column $columnOld to $columnNew");
  }

  public function setColumnName($table, $columnOld, $columnNew){
    $this->renameColumn($table, $columnOld, $columnNew);
  }

  /*
  public function removeColumn($table, $column){
    
  }

  public function setColumnType($table, $column, $type){
    
  }

  public function getColumnType($table, $column){
    
  }

  public function setColumnDefault($table, $column, $default){
    
  }

  public function getColumnDefault($table, $column){
    
  }
   
   */

// </editor-fold>
// <editor-fold defaultstate="expanded" desc="structure info">

  public function getTableNames(){
    $db          = $this->db;
    $tablesquery = $db->query("SELECT name FROM sqlite_master WHERE type='table';");

    $tables = [];
    while($table  = $tablesquery->fetchArray(SQLITE3_ASSOC)){
      $tables[] = $table['name'];
    }
    return $tables;
  }

  public function getColumnNames($table){
    return array_keys($this->getColumnsInfo($table));
  }

  public function getColumnInfo($table, $column){
    return $this->getColumnsInfo($table)[$column]??null;
  }

// </editor-fold>

  public function doExecute($sql, $params = []){
    //var_dump($sql); exit;
    $stmt = $this->db->prepare($sql);
    foreach($params as $k => $v){
      $stmt->bindValue(':' . $k, $v);
    }
    $stmt->execute();
  }

  public function getColumnsInfo(string $table): array{
    $db    = $this->db;
    $query = $db->query("PRAGMA table_info(" . $table . ");");
    $rows  = [];
    while($row   = $query->fetchArray(SQLITE3_ASSOC)){
      $rows[$row['name']] = $row;
    }
    return $rows;
  }

}

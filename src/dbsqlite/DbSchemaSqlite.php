<?php

namespace kmucms\dbsqlite;

class DbSchemaSqlite{

  //private bool $errors       = false;
  //private string $errorMessage = '';
  private string $dbFile;
  private array $dbSchema;
  private auxiliaryPrivate\DbStructureSqlite $dbs;
  private DbSqlite $db;

  public function __construct(string $dbFile, array $dbSchema){
    $this->dbFile   = $dbFile;
    $this->dbSchema = $dbSchema;
    $this->dbs      = new auxiliaryPrivate\DbStructureSqlite($dbFile);
    $this->db       = new DbSqlite($dbFile);
  }

  public function update(bool $useVersioning = true): void{
    if(!in_array('db_schema_info', $this->dbs->getTableNames())){ //is new
      $schemaInfoTable = $this->getTableSql('db_schema_info', ['columns' => [['name' => 'k'], ['name' => 'v']]]);
      $this->dbs->doExecute($schemaInfoTable);
      $this->db->addRow('db_schema_info', ['k' => 'version', 'v' => '0']);
    }
    $dbVersion = intval($this->db->getRow("select v from db_schema_info where k='version'")['v']);
    if($useVersioning && intval($this->dbSchema['version']) <= $dbVersion){
      //version in updatearray is smaller than current db version, nothing to do
      return;
    }
    $this->renameTablesAndColumns();
    foreach($this->dbSchema['tables'] as $table){
      if(in_array($table['name'], $this->dbs->getTableNames())){//does table exist
        if(!$this->isTableAsDescribed($table)){
          //create new table
          $tempTableName = $this->getTempTableName();
          $newTableSql   = $this->getTableSql($tempTableName, $table);
          $this->dbs->doExecute($newTableSql);
          //copy data 
          $this->copyData($table['name'], $tempTableName);
          $this->dbs->removeTable($table['name']);
          $this->dbs->renameTable($tempTableName, $table['name']);
        }
      }else{//table did not exist
        $sql = $this->getTableSql($table['name'], $table);
        $this->dbs->doExecute($sql);
      }
    }
  }

  /*
    public function hasErrors(): bool{
    return $this->errors;
    }

    public function getErrorMessage(): string{
    return $this->errorMessage;
    }
   */

  private function getTempTableName():string{
    $i      = 1;
    $name   = 'temptable';
    $tables = $this->dbs->getTableNames();

    $tempName = $name . $i;

    while(in_array($tempName, $tables)){
      $i++;
      $tempName = $name . $i;
    }

    return $tempName;
  }

  private function getTableSql(string $tableName, array $tableSchema){
    $columns = "id INTEGER PRIMARY KEY AUTOINCREMENT ";
    foreach($tableSchema['columns'] as $column){
      $name    = $column['name'];
      $type    = $column['type'] ?? 'string';
      $default = isset($column['default']) ? "default '{$column['default']}'" : '';
      $columns .= ", $name $type $default";
    }

    $res = "create table IF NOT EXISTS $tableName ( $columns );";

    return $res;
  }

  private function isTableAsDescribed(array $tableSchema){
    if(!in_array($tableSchema['name'], $this->dbs->getTableNames())){
      return false;
    }
    $tabCol = $this->dbs->getColumnsInfo($tableSchema['name']);
    foreach($tableSchema['columns'] as $column){
      if(!isset($tabCol[$column['name']])){
        return false;
      }
      $colinfo = $tabCol[$column['name']];
      if($colinfo['type'] != ($column['type'] ?? 'string')){
        return false;
      }
      if(isset($column['default']) && "'{$column['default']}'" != $colinfo['dflt_value']){
        return false;
      }
    }
    return true;
  }

  private function copyData(string $oldTableName, string $newTableName){
    $colOld = $this->dbs->getColumnNames($oldTableName);
    $colNew = $this->dbs->getColumnNames($newTableName);
    $common = array_intersect($colOld, $colNew);
    $cols   = implode(',', $common);
    $this->dbs->doExecute("INSERT INTO $newTableName($cols) SELECT $cols FROM $oldTableName;");
  }

  private function renameTablesAndColumns(){
    if(!isset($this->dbSchema['rename'])){
      return;
    }
    foreach($this->dbSchema['rename']['tables'] as $from => $to){
      $this->dbs->renameTable($from, $to);
    }
    foreach($this->dbSchema['rename']['columns'] as $table => $col){
      foreach($col as $from => $to){
        $this->dbs->renameColumn($table, $from, $to);
      }
    }
  }

}

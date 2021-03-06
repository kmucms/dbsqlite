Helping classes for using sqlite db.

## WORKING WITH DB

```php
//to make live easier i use allways tables with a column='id'

$db = new \kmucms\dbsqlite\DbSqlite('/path/to/file.sqli');

//read some rows
$row = $db->getRow("select * from page where id=5 limit 1");
$rows = $db->getRows("select * from page where id=5");
$rows = $db->getRows("select * from page where id=:id",['id'=>5]);
$row = $db->getRowById('page', 3);
$rows = $db->getRowByColumn('page', 'type', 'news'); // all rows with type='news'
$count = $db->getRowsCount('person', "first_name like 'a%'");

//manipulate some rows
$id = $db->addRow('person', ['first_name'=>'Max','last_name'=>'Maier']);
$db->setRow('person', $id, ['first_name'=>'Peter']);
$db->removeRow('person', $id);
```

## CREATING DB

Set version +1 if something has changed and schema needs an update.
"rename" section is performed first, so remove it if there is nothing to rename. 
Rename tables is the first renaming.
Rename columns is the second renaming, for columns use only the new table-names.
The id-row is added to every table.
db_schema_info table is added to track current version.

```php
$schemaDescription = [
    'version' => 1,
    'tables' => [
        [
            'name' => 'person',
            'columns' => [
                [
                    'name' => 'first_name',
                    'type' => 'TEXT',  //INTEGER,REAL,TEXT,BLOB,NUMERIC
                    'default' => 'Maxim',
                ],
                [
                    'name' => 'last_name',
                    'type' => 'TEXT',
                    'default' => 'Maier',
                ],
            ],
        ],
        [
            'name' => 'car',
            'columns' => [
                [
                    'name' => 'brand',
                    'type' => 'TEXT',
                ],
                [
                    'name' => 'price',
                    'type' => 'INTEGER',
                ],
            ],
        ],
    ],
    'rename' => [
        'tables' => [
            'bike' => 'car',
            //'fromTable' => 'toTable',
        ],
        'columns' => [
            'car' => [
                'value' => 'price',
                //'fromColumn' => 'toColumn',
            ],
        ],
    ],
];

$c = new \kmucms\dbsqlite\DbSchemaSqlite(__DIR__.'/file.sqli', $schemaDescription);
$c->update();

```

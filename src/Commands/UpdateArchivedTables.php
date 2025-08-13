<?php

namespace Kroesen\LaravelAdditions\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Kroesen\LaravelAdditions\Helpers\Blueprint;
use Schema;

class UpdateArchivedTables extends Command
{

    protected $signature = 'kroesen:archive:update-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update archive models';

    public function handle()
    {
        $this->info('Update archive tables');

        $models = config('laravel_additions.models');

        foreach ($models as $model => $options) {

            if (defined($model.'::TRANSLATION_CLASS') && defined($options['archive_model'].'::TRANSLATION_CLASS')) {
                $models[$model::TRANSLATION_CLASS] = [
                    'archive_model' => (new $options['archive_model'])::TRANSLATION_CLASS,
                ];
            }
        }

        $alterTables = [];
        /**
         * @var Model $model
         * @var array $options
         */
        foreach ($models as $model => $options){

            $modelObject = new $model;
            /** @var Model $archiveObject */
            $archiveObject = new $options['archive_model'];
            $connection = Schema::connection($modelObject->getConnectionName());

            $columns = $connection->getColumns($modelObject->getTable());
            $indexes = $connection->getIndexes($modelObject->getTable());
//            $foreignKeys = $connection->getForeignKeys($modelObject->getTable());

            $schema = Schema::connection($archiveObject->getConnectionName());

            if(!$schema->hasTable($archiveObject->getTable())){
                // Create
                $columns = $this->convertColumns($columns);
                $indexes = $this->convertIndexes($archiveObject->getTable(), $indexes);
//                $foreignKeys = $this->convertForeignKeys($archiveObject->getTable(), $foreignKeys);
                $createTableContent = implode(",\n", array_merge($columns, $indexes['constraints']));
                $alterTables = array_merge($alterTables, $indexes['indexes']);

                \DB::connection($archiveObject->getConnectionName())->statement("
                    CREATE TABLE {$archiveObject->getTable()} (
                        $createTableContent
                    );
                ");

            }else{
                // Update

                $archivedColumns = $schema->getColumns($archiveObject->getTable());
                $archivedIndexes = $schema->getIndexes($archiveObject->getTable());
//                $archivedForeignKeys = $schema->getForeignKeys($archiveObject->getTable());


//                // Update foreign keys
//                $modifyForeignKeys = $this->compare($foreignKeys, $archivedForeignKeys, 'name');
//                $addForeignKeys = $modifyForeignKeys['add'];
//                $removeForeignKeys = $modifyForeignKeys['remove'];
//                foreach ($modifyForeignKeys['change'] as $foreignKey){
//                    $removeForeignKeys[] = $foreignKey;
//                    $addForeignKeys[] = $foreignKey;
//                }
//                array_walk($removeForeignKeys, function (&$col) use ($archiveObject) {
//                    $col = "ALTER TABLE {$archiveObject->getTable()} DROP FOREIGN KEY {$col['name']}";
//                });
//                $addForeignKeys = $this->convertForeignKeys($archiveObject->getTable(), $addForeignKeys);
//                $alterTables = array_merge($alterTables, $removeForeignKeys);

                // Update indexes
                $modifyIndexes = $this->compare($indexes, $archivedIndexes, 'name');
                $addIndexes = $modifyIndexes['add'];
                $removeIndexes = $modifyIndexes['remove'];
                foreach ($modifyIndexes['change'] as $index){
                    $removeIndexes[] = $index;
                    $addIndexes[] = $index;
                }
                array_walk($removeIndexes, function (&$col) use ($archiveObject) {
                    $col = "ALTER TABLE {$archiveObject->getTable()} DROP INDEX {$col['name']}";
                });
                $addIndexes = $this->convertIndexes($archiveObject->getTable(), $addIndexes);
                $alterTables = array_merge($alterTables, $removeIndexes);

                // Update columns
                $modifyColumns = $this->compare($columns, $archivedColumns, 'name');
                $changeColumns = $this->convertColumns($modifyColumns['change']);
                $addColumns = $this->convertColumns($modifyColumns['add']);
                $removeColumns = $modifyColumns['remove'];
                array_walk($changeColumns, function (&$col) use ($archiveObject) {
                    $col = "ALTER TABLE {$archiveObject->getTable()} MODIFY COLUMN $col";
                });
                array_walk($addColumns, function (&$col) use ($archiveObject) {
                    $col = "ALTER TABLE {$archiveObject->getTable()} ADD $col";
                });
                array_walk($removeColumns, function (&$col) use ($archiveObject) {
                    $col = "ALTER TABLE {$archiveObject->getTable()} DROP COLUMN {$col['name']}";
                });

                $alterTables = array_merge(
                    $alterTables,
                    $changeColumns,
                    $addColumns,
                    $removeColumns,
                    $addIndexes['indexes'],
//                    $addForeignKeys
                );
                $alterTables = array_unique($alterTables);
            }

            foreach ($alterTables as $alterTable){
                $this->info($alterTable);
                \DB::connection($archiveObject->getConnectionName())->statement($alterTable);
            }
        }
    }

    private function convertColumns(array $columns): array
    {
        foreach ($columns as $k => $column){
            $string = '`' . $column['name'] . '` ' . $column['type'];
            if(isset($column['collation'])){
                $string .= ' collate ' . $column['collation'];
            }
            if(isset($column['nullable']) && $column['nullable']){
                $string .= ' NULL';
            }else{
                $string .= ' NOT NULL';
            }
            if(isset($column['auto_increment']) && $column['auto_increment']){
                $string .= ' auto_increment';
            }
            $columns[$k] = $string;
        }
        return $columns;
    }

    private function convertIndexes(string $tableName, array $indexes): array
    {
        $constraintsData = [];
        $indexesData = [];

        foreach ($indexes as $k => $index){

            array_walk($index['columns'], fn(&$col) => $col = "`$col`");
            $columns = implode(',', $index['columns']);
            if(isset($index['primary']) && $index['primary']){
                $constraintsData[] = sprintf('constraint `%s` primary key (%s)', $index['name'], $columns);
                continue;
            }

            $indexesData[] = sprintf('create %s index `%s` on %s (%s)',
                (isset($index['unique']) && $index['unique'] ? 'unique' : ''),
                $index['name'],
                $tableName,
                $columns
            );

        }

        return [
            'constraints' => $constraintsData,
            'indexes' => $indexesData,
        ];
    }

    private function convertForeignKeys(string $tableName, array $foreignKeys): array
    {
        $data = [];
        foreach ($foreignKeys as $key){
            array_walk($key['columns'], fn(&$col) => $col = "`$col`");
            array_walk($key['foreign_columns'], fn(&$col) => $col = "`$col`");
            $data[] = sprintf('alter table `%s` add constraint `%s` foreign key (%s) references %s.%s (%s) on update %s on delete %s',
                $tableName,
                $key['name'],
                implode(',', $key['columns']),
                $key['foreign_schema'],
                $key['foreign_table'],
                implode(',', $key['foreign_columns']),
                $key['on_update'],
                $key['on_delete']
            );

        }

        return $data;
    }

    private function compare(array $first, array $second, string $field): array
    {
        $change = [];
        foreach ($first as $k1 => $v1){
            foreach ($second as $k2 => $v2){
                if($v1[$field] === $v2[$field]){
                    if($v1 !== $v2){
                        $change[] = $v1;
                    }
                    unset($first[$k1]);
                    unset($second[$k2]);
                    break;
                }
            }
        }

        return [
            'change' => $change,
            'add' => $first,
            'remove' => $second,
        ];
    }
}

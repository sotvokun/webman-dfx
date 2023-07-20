<?php

namespace Sotvokun\Webman\Dfx\Support\Phinx;

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Table as PhinxTable;

class TableBuilder
{
    private PhinxTable $table;
    private bool $isTableExists = false;

    public function __construct(private AbstractMigration $migration, string $tableName, array $options = [])
    {
        $this->table = $this->migration->table($tableName, $options);
        $this->isTableExists = $this->migration->hasTable($tableName);
    }

    /**
     * Set columns for table
     * @param array $columns
     * @return TableBuilder
     */
    public function setColumns(array $columns): TableBuilder
    {
        foreach ($columns as $column) {
            $this->table->addColumn($column[0], $column[1], $column[2] ?? []);
        }

        return $this;
    }

    /**
     * Set index for table
     * @param array $index
     * @return TableBuilder
     */
    public function setIndex(array $index): TableBuilder
    {
        foreach ($index as $item) {
            $this->table->addIndex($item[0], $item[1] ?? []);
        }

        return $this;
    }

    /**
     * Set foreign keys for table
     * @param array $forienKeys
     * @return TableBuilder
     */
    public function setForeignKeys(array $forienKeys): TableBuilder
    {
        foreach ($forienKeys as $item) {
            $this->table->addForeignKey($item[0], $item[1], $item[2], $item[3]);
        }

        return $this;
    }

    /**
     * Set timestamp columns for table
     * @param string $createFieldName Name of the timestamp column for creation, default: created_at
     * @param string $updateFieldName Name of the timestamp column for update, default: updated_at
     *
     * SQL:
     * ```sql
     * `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     * `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
     * ```
     */
    public function setTimestamp(
        string $createFieldName = 'created_at',
        string $updateFieldName = 'updated_at'
    ): TableBuilder
    {
        $this->table->addColumn($createFieldName, 'datetime', [
            'default' => 'CURRENT_TIMESTAMP'
        ]);
        $this->table->addColumn($updateFieldName, 'datetime', [
            'default' => 'CURRENT_TIMESTAMP',
            'update' => 'CURRENT_TIMESTAMP'
        ]);

        return $this;
    }

    /**
     * Set soft deletable columns for table
     * @param string $columnName Name of the soft deletable column, default: deleted_at
     *
     * SQL:
     * ```sql
     * `deleted_at` DATETIME DEFAULT NULL
     * ```
     */
    public function setSoftDeletable(string $columnName = 'deleted_at'): TableBuilder
    {
        $this->table->addColumn($columnName, 'datetime', [
            'null' => true
        ]);

        return $this;
    }

    /**
     * Set disable columns for table.
     * This column usually used for reprensenting the status of the entity.
     * @param string $columnName Name of the disable column, default: disabled_at
     *
     * SQL:
     * ```sql
     * `disabled_at` DATETIME DEFAULT NULL
     * ```
     */
    public function setDisable(string $columnName): TableBuilder
    {
        $this->table->addColumn($columnName, 'datetime', [
            'null' => true
        ]);

        return $this;
    }

    /**
     * Reject phinx table object
     * @return \Phinx\Db\Table
     */
    public function reject(): PhinxTable
    {
        return $this->table;
    }

    /**
     * Create or update table
     */
    public function save()
    {
        if ($this->isTableExists) {
            $this->table->update();
        } else {
            $this->table->create();
        }
    }

    /**
     * Get table name from model name
     * @param string $modelName
     * @return string
     */
    public static function getModelTableName(string $modelName, ?string $parentClass = null)
    {
        if (!class_exists($modelName) ||
            !is_subclass_of($modelName, $parentClass ?? \support\Model::class)) {
                throw new \InvalidArgumentException("Model $modelName not found");
        }
        return (new $modelName)->getTable();
    }
}

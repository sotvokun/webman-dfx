<?php

namespace Sotvokun\Webman\Dfx\Support\Phinx;

use Phinx\Migration\AbstractMigration;


class Migration extends AbstractMigration
{
    public function tableBuilder(string $modelName, array $options = [])
    {
        $tableName = TableBuilder::getModelTableName($modelName);
        return new TableBuilder($this, $tableName, $options);
    }
}

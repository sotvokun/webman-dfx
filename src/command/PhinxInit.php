<?php

namespace Sotvokun\Webman\Dfx\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

use function Sotvokun\Webman\Dfx\dfx_path;

class PhinxInit extends Command
{
    const FIELD_MAP = [
        'driver' => 'adapter',
        'host' => 'host',
        'port' => 'port',
        'database' => 'name',
        'username' => 'user',
        'password' => 'pass',
        'charset' => 'charset',
        'prefix' => 'table_prefix',
        'suffix' => 'table_suffix',
    ];

    const OPTION_CONNECTION = 'connection';
    const OPTION_MIGRATIONS_PATH = 'migrations-path';
    const OPTION_SEEDS_PATH = 'seeds-path';
    const OPTION_MIGRATION_TABLE = 'migration-table';
    const OPTION_BASE_CLASS = 'base-class';

    protected static $defaultName = 'dfx:phinx/init';
    protected static $defaultDescription = 'Initialize or synchronize phinx migration configuration';

    protected function configure()
    {
        $dbConfig = config('database');
        $this->addArgument(
            self::OPTION_CONNECTION,
            key_exists('default', $dbConfig) ? InputArgument::OPTIONAL : InputArgument::REQUIRED,
            'The database connection to use',
            $dbConfig['default'] ?? null
        );
        $this->addOption(
            self::OPTION_MIGRATIONS_PATH,
            null,
            InputArgument::OPTIONAL,
            'The path of migration files',
            base_path() . '/database/migrations'
        );
        $this->addOption(
            self::OPTION_SEEDS_PATH,
            null,
            InputArgument::OPTIONAL,
            'The path of seed files',
            base_path() . '/database/seeds'
        );

        $this->addOption(
            self::OPTION_MIGRATION_TABLE,
            null,
            InputArgument::OPTIONAL,
            'The name of migration table',
            '__phinxlog'
        );

        $this->addOption(
            self::OPTION_BASE_CLASS,
            null,
            InputArgument::OPTIONAL,
            'The base class of migration'
        );
    }

    private function executeMakePhinxLink()
    {
        $phinxPath = base_path() . '/phinx';
        if (file_exists($phinxPath)) {
            return;
        }

        $binPath = base_path() . '/vendor/bin/phinx';
        symlink($binPath, $phinxPath);
    }

    protected function execute($input, $output)
    {
        $this->executeMakePhinxLink();

        $dbConfig = config('database');
        $migrationsPath = $input->getOption(self::OPTION_MIGRATIONS_PATH);
        $seedsPath = $input->getOption(self::OPTION_SEEDS_PATH);
        $migrationTable = $input->getOption(self::OPTION_MIGRATION_TABLE);

        if (!is_dir($migrationsPath)) {
            mkdir($input->getOption('migrations-path'), 0755, true);
        }
        if (!is_dir($seedsPath)) {
            mkdir($input->getOption('seeds-path'), 0755, true);
        }

        $contents = file_get_contents(dfx_path() . '/src/data/phinx.yml.dist');
        $classes = [
            '$migrationsPath' => $migrationsPath,
            '$seedsPath' => $seedsPath,
            '$migrationTable' => $migrationTable,
            '$defaultEnvironment' => $input->getArgument(self::OPTION_CONNECTION)
        ];

        if (!is_array($dbConfig['connections'])) {
            $output->writeln('`database.php\' does not have `connections\' key');
            $classes['$environments'] = '';
        } else {
            $environments = $this->convertConnectionsToEnvironments($dbConfig['connections'], true);
            $environments = substr($environments, 0, -1);
            $classes['$environments'] = $environments;
        }

        $contents = strtr($contents, $classes);

        $baseClass = $input->getOption(self::OPTION_BASE_CLASS);
        if ($baseClass) {
            $contents .= "\nmigration_base_class: {$baseClass}\n";
        }

        $filePath = base_path() . '/phinx.yml';
        if (file_put_contents($filePath, $contents) === false) {
            throw new RuntimeException('Failed to write to phinx.yml');
        }

        $output->writeln('Migration configuration has been initialize');
        return 0;
    }

    private function convertConnectionsToEnvironments(array $connections, bool $toString): array|string
    {
        $result = [];
        foreach ($connections as $name => $dbCfg) {
            $result[$name] = $this->convertDatabaseToEnvironment($dbCfg);
        }
        if (!$toString) {
            return $result;
        }

        $resultStr = '';
        foreach ($result as $name => $env) {

            $resultStr .= str_repeat(' ', 4) . "{$name}:\n";
            foreach ($env as $key => $value) {
                $resultStr .= str_repeat(' ', 8) . "{$key}: {$value}\n";
            }
        }
        return $resultStr;
    }

    private function convertDatabaseToEnvironment(array $dbCfg): array
    {
        $result = [];
        foreach (self::FIELD_MAP as $dbKey => $envKey) {
            if (key_exists($dbKey, $dbCfg)) {
                $result[$envKey] = $dbCfg[$dbKey];
            }
        }
        return $result;
    }

}

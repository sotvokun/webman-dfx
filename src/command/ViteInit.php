<?php

namespace Sotvokun\Webman\Dfx\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Sotvokun\Webman\Dfx\dfx_path;

class ViteInit extends Command
{
    const VITE_FILE = 'vite.config.js';
    const NODE_FILE = 'package.json';

    protected static $defaultName = 'dfx:vite/init';
    protected static $defaultDescription = 'Initialize vite configuration';

    protected function configure()
    {
        $this->addOption('entry', 'e', InputArgument::OPTIONAL, 'Entry file', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $viteTemp = dfx_path() . '/src/data/vite.config.js.dist';
        $nodeTemp = dfx_path() . '/src/data/package.json.dist';

        $viteDist = base_path() . DIRECTORY_SEPARATOR . self::VITE_FILE;
        $nodeDist = base_path() . DIRECTORY_SEPARATOR . self::NODE_FILE;

        var_dump($nodeDist);

        if (!file_exists($viteDist)) {
            $contents = file_get_contents($viteTemp);
            $classes = [
                '$entry' => $input->getOption('entry'),
            ];
            $contents = strtr($contents, $classes);
            if (file_put_contents($viteDist, $contents) === false) {
                throw new RuntimeException('Failed to write file ' . $viteDist);
            }
        }

        if (!file_exists($nodeDist)) {
            $contents = file_get_contents($nodeTemp);
            if (file_put_contents($nodeDist, $contents) === false) {
                throw new RuntimeException('Failed to write file ' . $nodeDist);
            }
        }

        return 0;
    }
}

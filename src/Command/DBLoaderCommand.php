<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DBLoaderCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:db:load');
        $this->addArgument('host');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        chdir(__DIR__);
        
        $process = new Process(['mongorestore', '--host', $input->getArgument('host')]);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
        });
    }
}


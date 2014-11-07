<?php
/**
 * @author    Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateGettyCSVCommand extends Command
{
    /**
     * OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('SmartGalleries:GenerateGettyCSV')
            ->setDescription('Generate Getty CSV')
            ->addArgument('jsonfile', InputArgument::REQUIRED, 'JSON file with SmartGalleries data');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Init
        $jsonfile = $input->getArgument('jsonfile');

        if (!file_exists($jsonfile)) {
            $output->writeln("<error>Don't found JSON file: '".$jsonfile."'</error>");
            die();
        }

        //Parsing JSON
        $count = 0;
        $json = file_get_contents($jsonfile);
        $allGalleries = json_decode($json,true);
        foreach ($allGalleries as $galleries)
        {
            foreach ($galleries as $gallery)
            {
                if (isset($gallery['gallery_item_set']))
                {
                    foreach ($gallery['gallery_item_set'] as $image)
                    {
                        if (isset($image['content']['metadata']['type']) AND $image['content']['metadata']['type'] == 'getty-images')
                        {
                            echo $image['content']['urls']['large'] . "\n";
                            $count++;
                        }
                    }
                }
            }
        }

        $output->writeln("<info>Count GETTY images: $count</info>");

    }
}
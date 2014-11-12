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
            ->addArgument('jsonfile', InputArgument::REQUIRED, 'JSON file with SmartGalleries data')
            ->addOption('gallery', null, InputOption::VALUE_NONE, 'If set, output gallery with getty images');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Init
        $jsonfile = $input->getArgument('jsonfile');

        $show_galleries = false;
        if ($input->getOption('gallery')) {
            $show_galleries = true;
        }

        if (!file_exists($jsonfile)) {
            $output->writeln("<error>Don't found JSON file: '".$jsonfile."'</error>");
            die();
        }

        //Parsing JSON
        $count = 0;
        $count_galleries = 0;
        $json = file_get_contents($jsonfile);
        $allGalleries = json_decode($json,true);
        foreach ($allGalleries as $galleries)
        {
            foreach ($galleries as $gallery)
            {
                $have_getty_image = false;
                if (isset($gallery['gallery_item_set']))
                {
                    foreach ($gallery['gallery_item_set'] as $image)
                    {
                        if (isset($image['content']['metadata']['type']) AND $image['content']['metadata']['type'] == 'getty-images')
                        {
                            $have_getty_image = true;
                            if (!$show_galleries) {
                                echo $image['content']['urls']['large'] . "\n";
                            }
                            $count++;
                        }
                    }
                }

                if ($have_getty_image AND $show_galleries)
                {
                    $count_galleries++;
                    echo 'http://stylecaster.galleries.newscred.com/galleries/#galleries/detail/'.$gallery['guid'] . "\n";
                }
            }
        }

        if ($show_galleries) {
            $output->writeln("<info>Count galleries with GETTY images: $count_galleries</info>");
        } else {
            $output->writeln("<info>Count GETTY images: $count</info>");
        }


    }
}
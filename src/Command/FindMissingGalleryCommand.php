<?php
/**
 * @author    Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindMissingGalleryCommand extends Command
{
    /**
     * OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('StyleCaster:GenerateCSV')
            ->setDescription('Create CSV with existing SmartGallery ids and JSON with all SmartGallery ids')
            ->addArgument('csvfile', InputArgument::REQUIRED, 'CSV file with existing SmartGallery ids')
            ->addArgument('jsonfile', InputArgument::REQUIRED, 'JSON file with all SmartGallery data');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Init
        $csvfile = $input->getArgument('csvfile');
        $jsonfile = $input->getArgument('jsonfile');
        $this->output = $output;

        if (!file_exists($csvfile)) {
            $output->writeln("<error>Don't found CSV file: '".$csvfile."'</error>");
            die();
        }

        if (!file_exists($jsonfile)) {
            $output->writeln("<error>Don't found JSON file: '".$jsonfile."'</error>");
            die();
        }

        //Parsing CSV
        $csvIds = array();
        $csv_file = fopen($csvfile, 'r');
        while (($data = fgetcsv($csv_file, 1000, ",")) !== FALSE) {
            array_push($csvIds, $data[0]);
        }
        fclose($csv_file);

        //Parsing JSON
        $json = file_get_contents($jsonfile);
        $allGalleries = json_decode($json,true);
        foreach ($allGalleries as $galleries)
        {
            foreach ($galleries as $gallery)
            {
                if (isset($gallery['provider_id']) and !in_array($gallery['provider_id'], $csvIds))
                {
                    echo $gallery['provider_id'] . "\n";
                }
            }
        }

    }
}
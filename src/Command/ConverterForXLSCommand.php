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

class ConverterForXLSCommand extends Command
{
    /**
     * OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('SmartGalleries:ConverterForXLS')
            ->setDescription('Generate CSV')
            ->addArgument('xmlfile', InputArgument::REQUIRED, 'XML file for handler')
            ->addArgument('outputfile', InputArgument::REQUIRED, 'output XML file')
            ->addOption('small', null, InputOption::VALUE_NONE, 'If set, generated small CSV');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Init
        $xmlfile = $input->getArgument('xmlfile');
        $outputfile = $input->getArgument('outputfile');
        $this->output = $output;

        $small = false;
        if ($input->getOption('small')) {
            $small = true;
        }

        if (!file_exists($xmlfile)) {
            $output->writeln("<error>Don't found XML file: '".$xmlfile."'</error>");
            die();
        }

        //Start parse XML
        $output->writeln("<info>Start parse XML</info>");
        $xml = simplexml_load_file($xmlfile);
        $output->writeln("<info>End parse XML</info>");

        //Check for valid XML file
        if (!isset($xml->channel))
        {
            $output->writeln("<error>This is not Wordpress export file'</error>");
            die();
        }
        $output->writeln("<info>Validation success</info>");

        $this->output->writeln("<info>Iterate posts:</info>");

        if ($csv_file = fopen($outputfile, 'a')) {
            //fputcsv($csv_file, array('post_id', 'post_url', 'smartgallry', 'date_published'), $delimiter = ',', $enclosure = '"');
            foreach ($xml->channel->xpath('//item') as $item) {
                $postType = isset($item->xpath('wp:post_type')[0]) ? (string)$item->xpath('wp:post_type')[0] : false;
                $postId = (string)$item->xpath('wp:post_id')[0];
                $postUrl = (string)$item->xpath('link')[0];
                $postPubDate = (string)$item->xpath('pubDate')[0];
                $hasSmartGallery = false;
                foreach ($item->xpath('wp:postmeta') as $meta) {
                    if ((string)$meta[0]->xpath('wp:meta_key')[0] == 'daylife_galleryId') {
                        if ((string)$meta[0]->xpath('wp:meta_value')[0]) {
                            $hasSmartGallery = true;
                        }
                    }
                }


                //$this->output->writeln('Post type: ' . $postType . ', ID: ' . $postId);
                if ($postType == 'post') {
                    if ($small) {
                        if ($hasSmartGallery) {
                            fputcsv($csv_file, array($postId, $postUrl, $postPubDate), $delimiter = ',', $enclosure = '"');
                        }
                    } else {
                        fputcsv($csv_file, array($postId, $postUrl, $hasSmartGallery ? 'Yes' : 'No', $postPubDate), $delimiter = ',', $enclosure = '"');
                    }
                }
            }
            fclose($csv_file);
        } else {
            $output->writeln("<error>Check rights for create CSV file</error>");
            die();
        }

    }
}
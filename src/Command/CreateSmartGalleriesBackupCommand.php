<?php
/**
 * @author    Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSmartGalleriesBackupCommand extends Command
{
    /**
     * OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('SmartGalleries:CreateBackup')
            ->setDescription('Create SmartGallery pictures backup')
            ->addArgument('jsonfile', InputArgument::REQUIRED, 'JSON file with SmartGalleries data')
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory for save SmartGalleries pictures');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Init
        $jsonfile = $input->getArgument('jsonfile');
        $directory = $input->getArgument('directory');
        ini_set("display_errors", 0);
        error_reporting(0);

        if (!file_exists($jsonfile)) {
            $output->writeln("<error>Don't found JSON file: '".$jsonfile."'</error>");
            die();
        }

        if (!file_exists($directory)) {
            $output->writeln("<error>Don't found directory: '".$jsonfile."'</error>");
            die();
        }

        //Parsing JSON
        $json = file_get_contents($jsonfile);
        $allGalleries = json_decode($json,true);

        $all_pictures_count = 0;
        foreach ($allGalleries as $galleries) {
            foreach ($galleries as $gallery) {
                if (isset($gallery['gallery_item_set'])) {
                    foreach ($gallery['gallery_item_set'] as $image) {
                        if (isset($image['content']['urls']['large']))
                        {
                            $all_pictures_count++;
                        }
                    }
                }
            }
        }
        $current_image = 0;
        $one_pictures_percentage = 100/$all_pictures_count;


        foreach ($allGalleries as $galleries)
        {
            foreach ($galleries as $gallery)
            {
                $galley_id = $gallery['guid'];
                $gallery_dir = $directory . $galley_id;
                if (!file_exists($gallery_dir))
                {
                    mkdir($gallery_dir);
                }
                $output->writeln("<info>Start backup gallery: $galley_id</info>");

                if (isset($gallery['gallery_item_set']))
                {
                    foreach ($gallery['gallery_item_set'] as $image)
                    {
                        if (isset($image['content']['urls']['large']))
                        {
                            $image_url = $image['content']['urls']['large'];
                            $image_guid = $image['content']['guid'];
                            if (!glob ($gallery_dir . '/' . $image_guid . '.*')) {
                                $retry_count = 0;
                                $image_data = file_get_contents($image_url);
                                while (!$image_data AND $retry_count < 10)
                                {
                                    $image_data = file_get_contents($image_url);
                                    $output->writeln("<error>Retry load image: $image_url</error>");
                                    sleep(2);
                                    $retry_count++;
                                }

                                if ($image_data) {
                                    $finfo = new \finfo(FILEINFO_MIME);
                                    $mime = strstr($finfo->buffer($image_data), ';', true);
                                    $extension = '.' . explode('/', $mime)[1];
                                    $filename = $image_guid . $extension;

                                    file_put_contents($gallery_dir . '/' . $filename, $image_data);
                                }
                            }

                            $current_image++;
                            $percentage = round($one_pictures_percentage*$current_image, 1);
                            $output->writeln("<info>$current_image / $all_pictures_count ($percentage %)</info>");
                        }
                    }
                }
            }

        }

        $output->writeln("<info>Backup created</info>");
    }
}
<?php
/**
 * @author    Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PostWithLostShortcodesCommand extends Command
{
    /**
     * OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('TheVivant:postwithlostshorcodes')
            ->setDescription('Find post with lost shortcodes')
            ->addArgument('xmlfile', InputArgument::REQUIRED, 'XML file for search');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Init
        $xmlfile = $input->getArgument('xmlfile');
        $this->output = $output;

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

        $this->iteratePost($xml); //Posts
    }

    /**
     * Iterate posts
     * @param /SimpleXMLElement $xml
     */
    protected function iteratePost($xml)
    {
        $lostShortcodes = array(
            '[contest',
            '[email',
            '[newsletter',
            '[twitter-follow',
            '[twitter-quote',
            '[twitter',
            '[joyus',
            '[amex',
            '[vmo',
            '[sc-tooltip',
            '[quiz-celebrity-style',
            '[quiz-face-shape',
            '[brightcove',
            '[vi-social-buttons',
            '[facebook',
        );
        $postsWithLostShortcodes = array();
        $this->output->writeln("<info>Iterate posts:</info>");
        foreach ($xml->channel->xpath('//item') as $item) {
            $postId = (string)$item->xpath('wp:post_id')[0];
            $content = (string)$item->xpath('content:encoded')[0];

            foreach ($lostShortcodes as $lostShortcode) {
                if (FALSE !== strpos($content, $lostShortcode)) {
                    $postsWithLostShortcodes[$lostShortcode][] = array(
                        'id' => $postId,
                        'title' => (string)$item->title[0],
                    );
                }
            }


        }

        var_dump($postsWithLostShortcodes);
    }

    /**
     * Iterate tags
     * @param /SimpleXMLElement $xml
     */
    protected function iterateTag($xml)
    {
        $this->output->writeln("<info>Iterate tags:</info>");
        foreach($xml->xpath('//wp:tag') as $tagXML) {
            $tagId = $tagXML->xpath('wp:term_id')[0];
            $tagSlug = $tagXML->xpath('wp:tag_slug')[0];
            $tagName = $tagXML->xpath('wp:tag_name')[0];

            $this->output->writeln('***************************************');
            $this->output->writeln('Before changes: ID: '. $tagId . ', SLUG: ' . $tagSlug . ', NAME: ' . $tagName);

            /*******CHANGE TAG **************
            $tagId->{0} = 'CHANGES_'.$tagId;
            $tagSlug->{0} = 'CHANGES_'.$tagSlug;
            $this->addCData($tagName, 'CHANGES_'.$tagName);
             ********************************/

            /*******REMOVE TAG **************
            unset($tagXML[0]);
            //********************************/

            $this->output->writeln('Before changes: ID: '. $tagId . ', SLUG: ' . $tagSlug . ', NAME: ' . $tagName);
        }
    }

    /**
     * Iterate categories
     * @param /SimpleXMLElement $xml
     */
    protected function iterateCategory($xml)
    {
        $this->output->writeln("<info>Iterate categories:</info>");
        foreach($xml->xpath('//wp:category') as $categoryXML) {
            $categoryId = $categoryXML->xpath('wp:term_id')[0];
            $categoryNicename = $categoryXML->xpath('wp:category_nicename')[0];
            $categoryParent = $categoryXML->xpath('wp:category_parent')[0];
            $catName = $categoryXML->xpath('wp:cat_name')[0];

            $this->output->writeln('***************************************');
            $this->output->writeln('Before changes: ID: '. $categoryId . ', NICENAME: ' . $categoryNicename . ', PARENT: ' . $categoryParent . ', NAME: ' . $catName);

            /*******CHANGE TAG **************
            $categoryId->{0} = 'CHANGES_'.$categoryId;
            $categoryNicename->{0} = 'CHANGES_'.$categoryNicename;
            $categoryParent->{0} = 'CHANGES_'.$categoryParent;
            $this->addCData($catName, 'CHANGES_'.$catName);
             ********************************/

            /*******REMOVE TAG **************
            unset($categoryXML[0]);
            //********************************/

            $this->output->writeln('Before changes: ID: '. $categoryId . ', NICENAME: ' . $categoryNicename . ', PARENT: ' . $categoryParent . ', NAME: ' . $catName);
        }
    }

    /**
     * Iterate authors
     * @param /SimpleXMLElement $xml
     */
    protected function iterateAuthor($xml)
    {
        $this->output->writeln("<info>Iterate authors:</info>");
        foreach($xml->xpath('//wp:author') as $authorXML) {
            $authorId = $authorXML->xpath('wp:author_id')[0];
            $authorLogin = $authorXML->xpath('wp:author_login')[0];
            $authorEmail = $authorXML->xpath('wp:author_email')[0];
            $authorDisplay_name = $authorXML->xpath('wp:author_display_name')[0];
            $authorFirst_name = $authorXML->xpath('wp:author_first_name')[0];
            $authorLast_name = $authorXML->xpath('wp:author_last_name')[0];

            $this->output->writeln('***************************************');
            $this->output->writeln('Before changes: ID: '. $authorId . ', LOGIN: ' . $authorLogin . ', EMAIL: ' . $authorEmail . ', DISPLAY_NAME: ' . $authorDisplay_name . ', FIRST_NAME: ' . $authorFirst_name . ', LAST_NAME: ' . $authorLast_name);

            /*******CHANGE TAG **************
            $authorId->{0} = 'CHANGES_'.$authorId;
            $authorLogin->{0} = 'CHANGES_'.$authorLogin;
            $authorEmail->{0} = 'CHANGES_'.$authorEmail;
            $this->addCData($authorDisplay_name, 'CHANGES_'.$authorDisplay_name);
            $this->addCData($authorFirst_name, 'CHANGES_'.$authorFirst_name);
            $this->addCData($authorLast_name, 'CHANGES_'.$authorLast_name);
             ********************************/

            /*******REMOVE TAG **************
            unset($authorXML[0]);
            //********************************/

            $this->output->writeln('Before changes: ID: '. $authorId . ', LOGIN: ' . $authorLogin . ', EMAIL: ' . $authorEmail . ', DISPLAY_NAME: ' . $authorDisplay_name . ', FIRST_NAME: ' . $authorFirst_name . ', LAST_NAME: ' . $authorLast_name);
        }
    }

    /**
     * Iterate terms
     * @param /SimpleXMLElement $xml
     */
    protected function iterateTerm($xml)
    {
        $this->output->writeln("<info>Iterate terms:</info>");
        foreach($xml->xpath('//wp:term') as $termXML) {
            $termId = $termXML->xpath('wp:term_id')[0];
            $termTaxonomy = $termXML->xpath('wp:term_taxonomy')[0];
            $termSlug = $termXML->xpath('wp:term_slug')[0];
            $termParent = isset($termXML->xpath('wp:term_parent')[0]) ? $termXML->xpath('wp:term_parent')[0] : false;
            $termName = $termXML->xpath('wp:term_name')[0];
            $termDescription = isset($termXML->xpath('wp:term_description')[0]) ? $termXML->xpath('wp:term_description')[0] : false;

            $this->output->writeln('***************************************');
            $this->output->writeln('Before changes: ID: '. $termId . ', TAXONOMY: ' . $termTaxonomy . ', SLUG: ' . $termSlug . ', PARENT: ' . $termParent . ', NAME: ' . $termName . ', DESCRIPTION: ' . $termDescription );

            /*******CHANGE TAG **************
            $termId->{0} = 'CHANGES_'.$termId;
            $termTaxonomy->{0} = 'CHANGES_'.$termTaxonomy;
            $termSlug->{0} = 'CHANGES_'.$termSlug;
            if ($termParent) $termParent->{0} = 'CHANGES_'.$termParent;
            $this->addCData($termName, 'CHANGES_'.$termName);
            if ($termDescription) $this->addCData($termDescription, 'CHANGES_'.$termDescription);
             ********************************/

            /*******REMOVE TAG **************
            unset($termXML[0]);
            //********************************/

            $this->output->writeln('Before changes: ID: '. $termId . ', TAXONOMY: ' . $termTaxonomy . ', SLUG: ' . $termSlug . ', PARENT: ' . $termParent . ', NAME: ' . $termName . ', DESCRIPTION: ' . $termDescription );
        }
    }

    /**
     * add CDATA value
     * @param /SimpleXMLElement $xml
     * @param string $cdata_text
     */
    private function addCData($xml, $cdata_text) {
        $node = dom_import_simplexml($xml);
        $no   = $node->ownerDocument;
        $node->nodeValue = '';
        $node->appendChild($no->createCDATASection($cdata_text));
    }
}
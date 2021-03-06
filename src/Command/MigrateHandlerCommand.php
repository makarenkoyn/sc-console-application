<?php
/**
 * @author    Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateHandlerCommand extends Command
{
    const NAME_POST_ID = 'tv2sc_tv_post_id';                   //Name post id in meta
    const NAME_POST_URL = 'tv2sc_tv_post_url';                 //Name post url in meta
    const NAME_ATTACHMENT_ID = 'tv2sc_tv_attachment_id';       //Name attachment id in meta
    const NAME_ATTACHMENT_URL = 'tv2sc_tv_attachment_url';     //Name attachment url in meta

    /**
     * OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('TheVivant:handlerforxml')
            ->setDescription('This handler from XML exported from TheVivant')
            ->addArgument('xmlfile', InputArgument::REQUIRED, 'XML file for handler')
            ->addArgument('outputfile', InputArgument::REQUIRED, 'output XML file');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Init
        $xmlfile = $input->getArgument('xmlfile');
        $outputfile = $input->getArgument('outputfile');
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

        //Iterating
        $this->iterateTag($xml); //Tags
        $this->iterateCategory($xml); //Categories
        $this->iterateAuthor($xml); //Authors
        $this->iterateTerm($xml); //Terms
        $this->iteratePost($xml); //Posts

//
        $output->writeln("<info>End modify data</info>");

        //Save
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $outputXMLArray = explode(PHP_EOL, $dom->saveXML());

        foreach ($outputXMLArray as $key => $row) {
            if (!trim($row)) {
                unset($outputXMLArray[$key]);
            }
        }

        $outputXML = implode(PHP_EOL, $outputXMLArray);
        file_put_contents($outputfile, $outputXML);
    }

    /**
     * Iterate posts
     * @param /SimpleXMLElement $xml
     */
    protected function iteratePost($xml)
    {
        $this->output->writeln("<info>Iterate posts:</info>");
        foreach ($xml->channel->xpath('//item') as $item) {
            $postType = isset($item->xpath('wp:post_type')[0]) ? (string)$item->xpath('wp:post_type')[0] : false;
            $postId = (string)$item->xpath('wp:post_id')[0];
            $postUrl = (string)$item->xpath('link')[0];
            $postGuid = (string)$item->xpath('guid')[0];

            $this->output->writeln('Post type: ' . $postType . ', ID: ' . $postId);
            switch ($postType) {
                case 'attachment':
                    //Attachment (media)

                    //Add id to meta
                    $meta = $item->addChild('wp:wp:postmeta', '');
                    $meta->addChild('wp:wp:meta_key', self::NAME_ATTACHMENT_ID);
                    $metaValue = $meta->addChild('wp:wp:meta_value', '');
                    $this->addCData($metaValue, $postId);

                    //Add url to meta
                    $meta = $item->addChild('wp:wp:postmeta', '');
                    $meta->addChild('wp:wp:meta_key', self::NAME_ATTACHMENT_URL);
                    $metaValue = $meta->addChild('wp:wp:meta_value', '');
                    $this->addCData($metaValue, $postGuid);

                    //Add internal tag
                    $internalTag = $item->addChild('category', '');
                    $internalTag->addAttribute('domain', 'sc_media_internal_tags');
                    $internalTag->addAttribute('nicename', 'the-vivant-media');
                    $this->addCData($internalTag, 'TheVivant media');

                    break;
                case 'post':
                    //Single post

                    //Add id to meta
                    $meta = $item->addChild('wp:wp:postmeta', '');
                    $meta->addChild('wp:wp:meta_key', self::NAME_POST_ID);
                    $metaValue = $meta->addChild('wp:wp:meta_value', '');
                    $this->addCData($metaValue, $postId);

                    //Add url to meta
                    $meta = $item->addChild('wp:wp:postmeta', '');
                    $meta->addChild('wp:wp:meta_key', self::NAME_POST_URL);
                    $metaValue = $meta->addChild('wp:wp:meta_value', '');
                    $this->addCData($metaValue, $postUrl);

                    //Add internal tag
                    $internalTag = $item->addChild('category', '');
                    $internalTag->addAttribute('domain', 'sc_post_internal_tags');
                    $internalTag->addAttribute('nicename', 'the-vivant-posts');
                    $this->addCData($internalTag, 'TheVivant posts');

                    //Reassigning categories
                    //<category domain="category" nicename="lifestyle"><![CDATA[Lifestyle]]></category>
                    foreach ( $item->category as $categoryTag)
                    {
                        if ($categoryTag->attributes()->domain  == 'category')
                        {
                            $oldNicename = (string)$categoryTag->attributes()->nicename;

                            switch((string)$oldNicename) {
                                case 'dining-nightlife':
                                    $this->addCData($categoryTag, 'Food & Drink');
                                    $categoryTag->attributes()->nicename = 'food-and-drink';
                                    break;
                                case 'lifestyle-culture':
                                    $this->addCData($categoryTag, 'Lifestyle');
                                    $categoryTag->attributes()->nicename = 'lifestyle';
                                    break;
                                case 'art-culture':
                                    $this->addCData($categoryTag, 'Lifestyle');
                                    $categoryTag->attributes()->nicename = 'lifestyle';
                                    break;
                                case 'lifestyle':
                                    $this->addCData($categoryTag, 'Lifestyle');
                                    $categoryTag->attributes()->nicename = 'lifestyle';
                                    break;
                                case 'shopping':
                                    break;
                                case 'travel':
                                    $this->addCData($categoryTag, 'Lifestyle');
                                    $categoryTag->attributes()->nicename = 'lifestyle';
                                    break;
                                case 'uncategorized':
                                    break;
                                case 'video':
                                    $this->addCData($categoryTag, 'Uncategorized');
                                    $categoryTag->attributes()->nicename = 'uncategorized';
                                    break;
                            }
                        }
                    }

                    break;
                case 'guest-author':
                    //Guest authors

                    //Some code this

                    break;
                case 'page':
                    //Page
                    unset($item[0]);
                    $this->output->writeln('Removed');
                    break;
                case 'nav_menu_item':
                    //Menu
                    unset($item[0]);
                    $this->output->writeln('Removed');
                    break;
            }
        }
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

            //Remove all categories
            unset($categoryXML[0]);

            $this->output->writeln('Before changes: ID: '. $categoryId . ', NICENAME: ' . $categoryNicename . ', PARENT: ' . $categoryParent . ', NAME: ' . $catName);
        }

        //Create categories
        $newCategory = $xml->channel->addChild('wp:wp:category', '');
        $newCategory->addChild('wp:wp:category_nicename', 'lifestyle');
        $newCategory->addChild('wp:wp:category_parent', '');
        $catName = $newCategory->addChild('wp:wp:cat_name', '');
        $this->addCData($catName, 'Lifestyle');

        $newCategory = $xml->channel->addChild('wp:wp:category', '');
        $newCategory->addChild('wp:wp:category_nicename', 'food-and-drink');
        $newCategory->addChild('wp:wp:category_parent', 'lifestyle');
        $catName = $newCategory->addChild('wp:wp:cat_name', '');
        $this->addCData($catName, 'Food & Drink');

        $newCategory = $xml->channel->addChild('wp:wp:category', '');
        $newCategory->addChild('wp:wp:category_nicename', 'shopping');
        $newCategory->addChild('wp:wp:category_parent', '');
        $catName = $newCategory->addChild('wp:wp:cat_name', '');
        $this->addCData($catName, 'Shopping');

        $newCategory = $xml->channel->addChild('wp:wp:category', '');
        $newCategory->addChild('wp:wp:category_nicename', 'uncategorized');
        $newCategory->addChild('wp:wp:category_parent', '');
        $catName = $newCategory->addChild('wp:wp:cat_name', '');
        $this->addCData($catName, 'Uncategorized');
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

        //Create Internal tags
        $integralTag = $xml->channel->addChild('wp:wp:term', '');
        $integralTag->addChild('wp:wp:term_taxonomy', 'sc_post_internal_tags');
        $integralTag->addChild('wp:wp:term_slug', 'the-vivant-posts');
        $integralTag->addChild('wp:wp:term_parent', '');
        $tagValue = $integralTag->addChild('wp:wp:term_name', '');
        $this->addCData($tagValue, 'TheVivant posts');

        $integralTag = $xml->channel->addChild('wp:wp:term', '');
        $integralTag->addChild('wp:wp:term_taxonomy', 'sc_media_internal_tags');
        $integralTag->addChild('wp:wp:term_slug', 'the-vivant-media');
        $integralTag->addChild('wp:wp:term_parent', '');
        $tagValue = $integralTag->addChild('wp:wp:term_name', '');
        $this->addCData($tagValue, 'TheVivant media');
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
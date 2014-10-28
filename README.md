sc-console-application
======================

SC/BH/TV/DM console application 


======================
For deploy console application:

1. Checkout this repository
2. Run composer update: `composer update`

======================
Convert TheVivant XML data dump to import XML file for StyleCaster:

`php bin/console TheVivant:handlerforxml inputXMLFile outputXMLFile`

For iterate many files:

`for i in $(seq -f "%01g" 1 6); do php bin/console command xml/v$i.xml; done`

======================
Find posts ID with lost shortcodes

`php bin/console TheVivant:postwithlostshorcodes inputXMLFile`
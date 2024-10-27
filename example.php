<?php

use ByJG\XmlUtil\XmlDocument;

require "vendor/autoload.php";

$xml = new XmlDocument('<root />');

$myNode = $xml->appendChild('mynode');
$xml->appendChild('subnode', 'text');
$xml->appendChild('subnode', 'more text');
$otherNode = $xml->appendChild('othersubnode', 'other text');
$otherNode->addAttribute('attr', 'value');

echo $xml->toString(true);

print_r($xml->toArray());


$node = $xml->selectSingleNode('//subnode');
echo $node->DOMNode()->nodeValue . "\n";
$node = $myNode->selectSingleNode('//subnode');
echo $node->DOMNode()->nodeValue . "\n";


$nodeList = $xml->selectNodes('//subnode');
foreach ($nodeList as $node)
{
    echo $node->nodeName;
}
echo "\n";

$nodeList = $myNode->selectNodes('//subnode');
foreach ($nodeList as $node)
{
    echo $node->nodeName;
}
echo "\n";


$xml->addNamespace('my', 'http://www.example.com/mytest/');
echo $xml->toString(true) . "\n";

$xml->appendChild('nodens', 'teste', 'http://www.example.com/mytest/');
$xml->appendChild('my:othernodens', 'teste');
echo $xml->toString(true) . "\n";

$nodeList = $xml->selectNodes('//my:othernodens', [ 'my' => 'http://www.example.com/mytest/' ] );
foreach ($nodeList as $node)
{
    echo 'A' . $node->nodeName;
}


//$str = '<?xml version="1.0" encoding="utf-8"'
//    . '<root xmlns:my="http://www.example.com/mytest/">'
//    . '    '
//    . '</root>';

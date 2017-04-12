<?php
/**
 * User: jg
 * Date: 12/04/17
 * Time: 12:14
 */

namespace Tests;

use ByJG\Util\CleanDocument;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class CleanDocumentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CleanDocument
     */
    protected $object;

    public function setUp()
    {
        $this->object = new CleanDocument(
            '<span >START <a href="http://www.pagecolumn.com/" nofollow>3 Column Layout Generator </a></span>' .
            '<span >Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            '<a name="aaa" href="http://www.pagecolumn.com/2_col_generator.htm">2 Column Layout Generator</a>' .
            ' END</span>'
        );
    }

    public function testStripAllTags()
    {
        $result = $this->object->stripAllTags();

        $this->assertEquals(
            'START 3 Column Layout Generator ' .
            'Middle  and ' .
            '2 Column Layout Generator' .
            ' END',
            $result
        );
    }

    public function testStripTags()
    {
        $this->object->stripTagsExcept(['img', 'link']);

        $this->assertEquals(
            'START 3 Column Layout Generator ' .
            'Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            '2 Column Layout Generator' .
            ' END',
            $this->object->get()
        );
    }

    public function testRemoveContentByTag()
    {
        $this->object->removeContentByTag('a');

        $this->assertEquals(
            '<span >START </span>' .
            '<span >Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            ' END</span>',
            $this->object->get()
        );
    }

    public function testRemoveContentByTag2()
    {
        $this->object->removeContentByTag('a', 'name');

        $this->assertEquals(
            '<span >START <a href="http://www.pagecolumn.com/" nofollow>3 Column Layout Generator </a></span>' .
            '<span >Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            ' END</span>',
            $this->object->get()
        );
    }

    public function testRemoveContentByTag3()
    {
        $this->object->removeContentByTag('a', 'href');

        $this->assertEquals(
            '<span >START </span>' .
            '<span >Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            ' END</span>',
            $this->object->get()
        );
    }

    public function testRemoveContentByTag4()
    {
        $this->object->removeContentByTag('a', 'nofollow');

        $this->assertEquals(
            '<span >START </span>' .
            '<span >Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            '<a name="aaa" href="http://www.pagecolumn.com/2_col_generator.htm">2 Column Layout Generator</a>' .
            ' END</span>',
            $this->object->get()
        );
    }

    public function testRemoveContentByProperty()
    {
        $this->object->removeContentByProperty('name');

        $this->assertEquals(
            '<span >START <a href="http://www.pagecolumn.com/" nofollow>3 Column Layout Generator </a></span>' .
            '<span >Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            ' END</span>',
            $this->object->get()
        );
    }

    public function testRemoveContentByProperty2()
    {
        $this->object->removeContentByProperty('nofollow');

        $this->assertEquals(
            '<span >START </span>' .
            '<span >Middle <img src="/imagem.png" > and <img src="/imagem2.png" />' .
            '<link rel="stylesheet" href="/some.css">' .
            '<a name="aaa" href="http://www.pagecolumn.com/2_col_generator.htm">2 Column Layout Generator</a>' .
            ' END</span>',
            $this->object->get()
        );
    }
}

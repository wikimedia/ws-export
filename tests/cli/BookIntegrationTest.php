<?php

require_once __DIR__ . '/../../cli/book.php';

class BookIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function bookProvider() {
        return array(
            array('The_Kiss_and_its_History', 'en'),
            array('Les_Fleurs_du_mal', 'fr')
        );
    }

    /**
     * @dataProvider bookProvider
     * @group integration
     */
    public function testCreateBookEpub2($title, $language) {
        $this->createBook($title, $language, 'epub-2');
    }

    /**
     * @dataProvider bookProvider
     * @group integration
     */
    public function testCreateBookEpub3($title, $language) {
        $this->createBook($title, $language, 'epub-3');
    }

    /**
     * @dataProvider bookProvider
     * @group integration
     */
    public function testCreateBookMobi($title, $language) {
        $this->createBook($title, $language, 'mobi');
    }

    private function createBook($title, $language, $format) {
        fwrite(STDERR, "createBook(" . $title . ", " . $language . ", " . $format .")");
        $output = createBook($title, $language, $format, sys_get_temp_dir(), array('credits' => false));
        $this->assertFileExists($output);
        fwrite(STDERR, " => " . $output . "\n");
        return $output;
    }
}

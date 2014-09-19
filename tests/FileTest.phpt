<?php

require __DIR__ . '/bootstrap.php';

use CDNLoader\File;
use Tester\TestCase;
use Tester\Assert;

class FileTest extends TestCase
{
  /** @var string */
  private $readFile;

  /** @var string */
  private $writeFile;

  /** @var string */
  private $toRemoveDir;


  protected function setUp()
  {
    $this->readFile = TEMP_DIR . '/.toRead';
    $this->writeFile = TEMP_DIR . '/data.txt';
    $this->toRemoveDir = TEMP_DIR . '/toRemove';

    if (!is_readable($this->readFile)) {
      chmod($this->readFile, 0555);
    }
  }


  protected function tearDown()
  {
    if (file_exists($this->writeFile)) {
      unlink($this->writeFile);
    }

    if (file_exists($this->toRemoveDir)) {
      rmdir($this->toRemoveDir);
    }
  }


  public function testNormalizePath()
  {
    $path = TEMP_DIR . '//subdirectory/.././';
    Assert::same(TEMP_DIR, File::normalizePath($path));
  }


  /**
   * @throws CDNLoader\InvalidArgumentException
   * @dataProvider getFilename
   */
  public function testCheckFilename($filename)
  {
    File::write($filename, '');
  }


  /**
   * Data provider
   */
  public function getFilename()
  {
    return array(
      // filename must be type of string
      array(true),
      array(123),
      array(12.3),
      array(array()),
      array(new stdClass),
      array(null),
      // filename cannot be empty string
      array('')
    );
  }


  /**
   * @throws CDNLoader\InvalidArgumentException
   */
  public function testRead_checkFileExists()
  {
    File::read(TEMP_DIR . '/not-exists.file');
  }


  /**
   * @throws CDNLoader\InvalidArgumentException
   */
  public function testRead_checkReadable()
  {
    chmod($this->readFile, 0);
    File::read($this->readFile);
  }


  public function testRead()
  {
    $expected = 'Lorem ipsum dolor sit amet.';
    $actual = File::read($this->readFile);
    Assert::same($expected, $actual);
  }


  /**
   * @throws CDNLoader\InvalidArgumentException
   */
  public function testWrite_checkWritable()
  {
    file_put_contents($this->writeFile, '');
    chmod($this->writeFile, 0);
    File::write($this->writeFile, '');
  }


  /**
   * @throws CDNLoader\InvalidArgumentException
   * @dataProvider getContent
   */
  public function testWrite_checkData($content)
  {
    File::write($this->writeFile, $content);
  }


  /**
   * Data provider
   */
  public function getContent()
  {
    return array(
      array(true),
      array(123),
      array(12.3),
      array(array()),
      array(new stdClass),
      array(null),
    );
  }


  public function testWrite()
  {
    $content = 'Lorem ipsum dolor sit amet.';

    File::write($this->writeFile, $content);
    Assert::same($content, file_get_contents($this->writeFile));

    File::write($this->writeFile, $content, true);
    Assert::same($content . $content, file_get_contents($this->writeFile));

    File::write($this->writeFile, $content);
    Assert::same($content, file_get_contents($this->writeFile));
  }


  public function testRemove_file()
  {
    mkdir($this->toRemoveDir);
    $filename = $this->toRemoveDir . '/toRemove.txt';
    file_put_contents($filename, '');

    File::remove($filename);
    Assert::false(file_exists($filename));
  }


  public function testRemove_directory()
  {
    mkdir($this->toRemoveDir);
    file_put_contents($this->toRemoveDir. '/toRemove.txt', '');

    File::remove($this->toRemoveDir);
    Assert::false(file_exists($this->toRemoveDir));
  }


  public function testRemove_emptyDirectory()
  {
    mkdir($this->toRemoveDir);
    file_put_contents($this->toRemoveDir . '/toRemove.txt', '');

    File::remove($this->toRemoveDir, true);
    Assert::true(file_exists($this->toRemoveDir));
    Assert::same(0, count(glob($this->toRemoveDir . '/*')));
  }

}

$testCase = new FileTest;
$testCase->run();
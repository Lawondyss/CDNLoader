<?php

require __DIR__ . '/bootstrap.php';

use CDNLoader\Compiler;
use Tester\Assert;

class CompilerTest extends \Tester\TestCase
{
  /** @var \CDNLoader\Compiler */
  private $compiler;

  /** @var array */
  private $optionLibraries = array(
    'jquery' => array(
      'name' => 'jquery',
      'version' => '2.1.1',
      'min' => true,
    ),
    'knockout' => array(
      'name' => 'knockout',
      'version' => '3.2.0',
      'files' => array(
        'knockout-min.js',
        'knockout-debug.js',
      ),
    ),
    'snowstorm' => array(
      'name' => 'Snowstorm',
      'version' => '20131208',
      'files' => 'snowstorm-min.js',
    ),
  );


  private $expectedLibraries = array(
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/knockout/3.2.0/knockout-min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/knockout/3.2.0/knockout-debug.js',
    'https://cdnjs.cloudflare.com/ajax/libs/Snowstorm/20131208/snowstorm-min.js',
  );


  protected function setUp()
  {
    $this->compiler = new Compiler;
  }


  public function testCreate()
  {
    Assert::type('CDNLoader\Compiler', $this->compiler);
  }


  public function testOutputDirectory()
  {
    Assert::null($this->compiler->getOutputDir());

    $returnValue = $this->compiler->setOutputDir(TEMP_DIR, false);
    Assert::type('CDNLoader\Compiler', $returnValue);

    Assert::same(TEMP_DIR, $this->compiler->getOutputDir());
  }


  public function testMakeOutputDirectory()
  {
    $outputDir = TEMP_DIR . '/toRemove';
    Assert::false(file_exists($outputDir));

    $this->compiler->setOutputDir($outputDir);
    Assert::true(file_exists($outputDir));

    rmdir($outputDir);
  }


  public function testLibraries()
  {
    $libraries = array(
      $this->optionLibraries['jquery'],
      $this->optionLibraries['knockout'],
      $this->optionLibraries['snowstorm'],
    );

    $returnValue = $this->compiler->setLibraries($libraries);
    Assert::type('CDNLoader\Compiler', $returnValue);
    Assert::same($this->expectedLibraries, $this->compiler->getLibraries());
  }


  public function testSetLibrary()
  {
    $this->compiler
      ->setLibrary($this->optionLibraries['jquery'])
      ->setLibrary($this->optionLibraries['knockout'])
      ->setLibrary($this->optionLibraries['snowstorm']);
    Assert::same($this->expectedLibraries, $this->compiler->getLibraries());
  }


  public function testGenerate()
  {
    $outputDir = TEMP_DIR . '/source';
    $this->compiler
      ->setLibraries($this->optionLibraries)
      ->setOutputDir($outputDir)
      ->generate();

    $expectedHash = '33a3dd5adafa1bf892033984ca4fb53f'; // MD5(serialize({libraries}))
    $hashFile = $outputDir . '/hash';
    Assert::same($expectedHash, file_get_contents($hashFile));

    $expectedFiles = array(
      $outputDir . '/cdn-libraries.js',
      $outputDir . '/hash',
    );
    Assert::same($expectedFiles, glob($outputDir . '/*'));

    Assert::same(array($outputDir . '/cdn-libraries.js'), $this->compiler->getFiles());

    foreach ($expectedFiles as $file) {
      unlink($file);
    }
    rmdir($outputDir);
  }

}

$testCase = new CompilerTest;
$testCase->run();
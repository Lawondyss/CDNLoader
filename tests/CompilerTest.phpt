<?php

require __DIR__ . '/bootstrap.php';

use CDNLoader\Compiler;
use Tester\Assert;

class CompilerTest extends \Tester\TestCase
{
  /** @var string */
  private $tempDir;

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
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1~jquery.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/knockout/3.2.0~knockout-min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/knockout/3.2.0~knockout-debug.js',
    'https://cdnjs.cloudflare.com/ajax/libs/Snowstorm/20131208~snowstorm-min.js',
  );


  protected function setUp()
  {
    $this->tempDir = TEMP_DIR . '/compiler-' . md5(microtime());
    mkdir($this->tempDir);

    $this->compiler = new Compiler;
  }


  protected function tearDown()
  {
    clearTemp($this->tempDir);
  }


  public function testCreate()
  {
    Assert::type('CDNLoader\Compiler', $this->compiler);
  }


  public function testOutputDirectory()
  {
    Assert::null($this->compiler->getOutputDir());

    $returnValue = $this->compiler->setOutputDir($this->tempDir, false);
    Assert::type('CDNLoader\Compiler', $returnValue);

    Assert::same($this->tempDir, $this->compiler->getOutputDir());
  }


  public function testMakeOutputDirectory()
  {
    $outputDir = $this->tempDir . '/toRemove';
    Assert::false(file_exists($outputDir));

    $this->compiler->setOutputDir($outputDir);
    Assert::true(file_exists($outputDir));
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
    $outputDir = $this->tempDir . '/source';
    $this->compiler
      ->setLibraries($this->optionLibraries)
      ->setOutputDir($outputDir)
      ->generate();

    $expectedHash = 'c6cf21d35c8cef68c0367a731a57e280'; // MD5(serialize({libraries}))
    $hashFile = $outputDir . '/hash';
    Assert::same($expectedHash, file_get_contents($hashFile));

    $expectedFiles = array(
      $outputDir . '/cdn-libraries.js',
      $outputDir . '/hash',
    );
    Assert::same($expectedFiles, glob($outputDir . '/*'));

    Assert::same(array($outputDir . '/cdn-libraries.js'), $this->compiler->getFiles());
  }

}

$testCase = new CompilerTest;
$testCase->run();
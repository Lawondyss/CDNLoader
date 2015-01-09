CDNLoader
=========

Tool for loading CSS and JS files from [cdnjs.com] into web pages.

Install
-------

Over [Composer]:
```
composer require lawondyss/cdnloader
```

Example
-------

Control factory in presenter:
```php
protected function createComponentCdnLoader()
{
    $compiler = new CDNLoader\Compiler;

    // set directory for save remote files
    $compiler->setOutputDir('cdntemp');

    // set libraries
    $compiler->setLibrary(array(
            'name' => 'jquery', // WARNING! Name is name from cdnjs.cloudflare.com URL!
            'version' => '2.1.1',
            'files' => array( // all the files that you want
                'jquery.min.js',
                'jquery.min.map',
            ),
        ))
        ->setLibrary(array(
            'name' => 'jspdf',
            'version' => '0.9.0rc1',
            'min' => TRUE, // load file `jspdf.min.js`, if FALSE or without, then load file `jspdf.js`
        ));

    // or shortcut
    $libraries = array(
        array(
            'name' => 'jquery',
            'version' => '2.1.1',
            'files' => array(
                'jquery.min.js',
                'jquery.min.map',
            ),
        ),
        array(
            'name' => 'jspdf',
            'version' => '0.9.0rc1',
            'min' => TRUE,
        )
    );
    $compiler->setLibraries($libraries);

    return new CDNLoader\Loader($compiler);
}
```

Control in template:
```
{control cdnLoader}
```

Example with Nette Framework extension
--------------------------------------

Configuration in config.neon:
```
extensions:
    cdnloader: CDNLoader\Extension

cdnloader:
    outputDir: cdntemp
    libraries:
        -
            name: jquery
            version: 2.1.1
            files:
                - jquery.min.js
                - jquery.min.map
        -
            name: jspdf
            version: 0.9.0rc1
            min: TRUE
```

Control factory in presenter:
```php
protected function createComponentCdnLoader()
{
    $factory = new CDNLoader\CDNLoaderFactory($this->context);
    return $factory->create();
}
```

[cdnjs.com]:http://cdnjs.com/
[Composer]:https://getcomposer.org/
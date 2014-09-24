<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Black\Bundle\GeneratorBundle\Tests\Generator;

use Black\Bundle\GeneratorBundle\Generator\DoctrineModelGenerator;

class DoctrineModelGeneratorTest extends GeneratorTest
{
    const FORMAT_XML = 'xml';

    const WITH_REPOSITORY = true;
    const WITHOUT_REPOSITORY = false;

    public function testGenerateXml()
    {
        $this->generate(self::FORMAT_XML, self::WITHOUT_REPOSITORY);

        $files = array(
            'Domain/Model/Foo.php',
            'Resources/config/doctrine/Foo.orm.xml',        
        );

        $this->assertFilesExists($files);
        $this->assertAttributesAndMethodsExists();
    }

    protected function assertFilesExists(array $files)
    {
        foreach ($files as $file) {
            $this->assertTrue(file_exists($this->tmpDir.'/'.$file), sprintf('%s has been generated', $file));
        }
    }

    protected function assertAttributesAndMethodsExists(array $otherStrings = array())
    {
        $content = file_get_contents($this->tmpDir.'/Domain/Model/Foo.php');

        $strings = array(
            'namespace Foo\\BarBundle\\Domain\\Model',
            'class Foo',
            'private $id',
            'private $bar',
            'private $baz',
            'public function getId',
            'public function getBar',
            'public function getBaz',
            'public function setBar',
            'public function setBaz',
        );

        $strings = array_merge($strings, $otherStrings);

        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }
    }

    protected function generate()
    {
        $this->getGenerator()->generate($this->getBundle(), 'Foo', $this->getFields());
    }

    protected function getGenerator()
    {
        $generator = new DoctrineModelGenerator($this->filesystem, $this->getRegistry());
        $generator->setSkeletonDirs(__DIR__.'/../../Resources/skeleton');

        return $generator;
    }

    protected function getBundle()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->will($this->returnValue($this->tmpDir));
        $bundle->expects($this->any())->method('getName')->will($this->returnValue('FooBarBundle'));
        $bundle->expects($this->any())->method('getNamespace')->will($this->returnValue('Foo\BarBundle'));

        return $bundle;
    }

    protected function getFields()
    {
        return array(
            array('fieldName' => 'bar', 'type' => 'string', 'length' => 255),
            array('fieldName' => 'baz', 'type' => 'integer', 'length' => 11),
        );
    }

    public function getRegistry() 
    {
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())->method('getManager')->will($this->returnValue($this->getManager()));
        $registry->expects($this->any())->method('getAliasNamespace')->will($this->returnValue('Foo\\BarBundle\\Domain\\Model'));

        return $registry;
    }

    public function getManager()
    {
        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($this->getConfiguration()));

        return $manager;
    }

    public function getConfiguration()
    {
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->any())->method('getEntityNamespaces')->will($this->returnValue(array('Foo\\BarBundle')));

        return $config;
    }

}

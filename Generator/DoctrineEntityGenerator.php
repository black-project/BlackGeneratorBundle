<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Black\Bundle\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Generates a Doctrine entity class based on its name, fields and format.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class DoctrineEntityGenerator extends Generator
{
    private $filesystem;
    private $registry;

    public function __construct(Filesystem $filesystem, RegistryInterface $registry)
    {
        $this->filesystem = $filesystem;
        $this->registry = $registry;
    }

    public function generate(BundleInterface $bundle, $entity, array $fields)
    {
        $entityClass = $this->registry->getAliasNamespace($bundle->getName()).'\\'.$entity;
        $entityPath = $bundle->getPath().'/Domain/Model/'.str_replace('\\', '/', $entity).'.php';
        if (file_exists($entityPath)) {
            throw new \RuntimeException(sprintf('Model "%s" already exists.', $entityClass));
        }

        $class = new ClassMetadataInfo($entityClass);
        $class->customRepositoryClassName = $bundle->getNamespace().'\\Infrastructure\\Persistence\\'.$entity.'OrmRepository';
        $class->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));
        $class->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
        foreach ($fields as $field) {
            $class->mapField($field);
        }

        $entityGenerator = $this->getEntityGenerator();
        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter('xml');
        $mappingPath = $bundle->getPath().'/Resources/config/doctrine/model/'.str_replace('\\', '.', $entity).'.orm.xml';

        if (file_exists($mappingPath)) {
            throw new \RuntimeException(sprintf('Cannot generate entity when mapping "%s" already exists.', $mappingPath));
        }
        $mappingCode = $exporter->exportClassMetadata($class);
        $entityGenerator->setGenerateAnnotations(false);
        $entityCode = $entityGenerator->generateEntityClass($class);

        $this->filesystem->mkdir(dirname($entityPath));
        file_put_contents($entityPath, $entityCode);

        if ($mappingPath) {
            $this->filesystem->mkdir(dirname($mappingPath));
            file_put_contents($mappingPath, $mappingCode);
        }

        $path = $bundle->getPath().str_repeat('/..', substr_count(get_class($bundle), '\\'));
        $this->getRepositoryGenerator()->writeEntityRepositoryClass($class->customRepositoryClassName, $path);
    }

    protected function getEntityGenerator()
    {
        $entityGenerator = new EntityGenerator();
        $entityGenerator->setGenerateAnnotations(false);
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(false);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->setNumSpaces(4);

        return $entityGenerator;
    }

    protected function getRepositoryGenerator()
    {
        return new EntityRepositoryGenerator();
    }
}

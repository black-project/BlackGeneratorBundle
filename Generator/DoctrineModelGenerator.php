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
use Sensio\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Generates a Doctrine entity class based on its name, fields and format.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class DoctrineModelGenerator extends Generator
{
    private $filesystem;
    private $registry;

    public function __construct(Filesystem $filesystem, RegistryInterface $registry)
    {
        $this->filesystem = $filesystem;
        $this->registry = $registry;
    }

    public function generate(BundleInterface $bundle, $model, array $fields)
    {
        // configure the bundle (needed if the bundle does not contain any Model yet)
        $config = $this->registry->getManager(null)->getConfiguration();

        $config->setEntityNamespaces(array_merge(
            array($config->getEntityNamespaces(), $bundle->getName() => $bundle->getNamespace().'\\Domain\\Model')

        ));

        $entityGenerator = new DoctrineEntityGenerator($this->filesystem, $this->registry);
        $entityGenerator->generate($bundle, $model, $fields);
    }

    public function isReservedKeyword($keyword)
    {
        return $this->registry->getConnection()->getDatabasePlatform()->getReservedKeywordsList()->isKeyword($keyword);
    }
}

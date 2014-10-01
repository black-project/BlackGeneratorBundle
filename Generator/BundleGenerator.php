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
use Symfony\Component\DependencyInjection\Container;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Generates a bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class BundleGenerator extends Generator
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generate($namespace, $bundle, $dir, $structure)
    {
        $dir .= '/'.strtr($namespace, '\\', '/');
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $basename = substr($bundle, 0, -6);
        $parameters = array(
            'namespace' => $namespace,
            'bundle'    => $bundle,
            'bundle_basename' => $basename,
            'extension_alias' => Container::underscore($basename),
        );

        $this->renderFile('bundle/Bundle.php.twig', $dir.'/'.$bundle.'.php', $parameters);
        $this->renderFile('bundle/Extension.php.twig', $dir.'/Application/DependencyInjection/'.$basename.'Extension.php', $parameters);
        $this->renderFile('bundle/Configuration.php.twig', $dir.'/Application/DependencyInjection/Configuration.php', $parameters);
        $this->renderFile('bundle/DefaultController.php.twig', $dir.'/Application/Controller/DefaultController.php', $parameters);
        $this->renderFile('bundle/DefaultControllerTest.php.twig', $dir.'/Tests/Application/Controller/DefaultControllerTest.php', $parameters);
        $this->renderFile('bundle/index.html.twig.twig', $dir.'/Resources/views/Default/index.html.twig', $parameters);

        $this->renderFile('bundle/services.xml.twig', $dir.'/Resources/config/services.xml', $parameters);
        $this->renderFile('bundle/orm.xml.twig', $dir.'/Resources/config/orm.xml', $parameters);
        $this->renderFile('bundle/mongodb.xml.twig', $dir.'/Resources/config/mongodb.xml', $parameters);

        if ($structure) {
            $this->filesystem->mkdir($dir.'/Resources/doc');
            $this->filesystem->touch($dir.'/Resources/doc/index.md');
            $this->filesystem->mkdir($dir.'/Resources/translations');
            $this->filesystem->mkdir($dir.'/Resources/public/css');
            $this->filesystem->mkdir($dir.'/Resources/public/images');
            $this->filesystem->mkdir($dir.'/Resources/public/js');

            $this->renderFile('bundle/messages.fr.yml.twig', $dir.'/Resources/translations/messages.fr.yml', $parameters);
        }
    }
}

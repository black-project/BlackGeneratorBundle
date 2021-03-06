<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Black\Bundle\GeneratorBundle\Command;

use Black\Bundle\GeneratorBundle\Generator\DoctrineModelGenerator;
use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineCommand;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\DBAL\Types\Type;


/**
 * Initializes a Doctrine model inside a bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateDoctrineModelCommand extends GenerateDoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('black:generate:model')
            ->setDescription('Generates a new Doctrine model inside a bundle')
            ->addOption('model', null, InputOption::VALUE_REQUIRED, 'The model class name to initialize (shortcut notation)')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'The fields to create with the new model')
            ->setHelp(<<<EOT
The <info>black:generate:model</info> task generates a new Doctrine
model inside a bundle:

<info>php app/console black:generate:model --model=AcmeBlogBundle:Blog/Post</info>

The above command would initialize a new model in the following model
namespace <info>Acme\BlogBundle\Model\Blog\Post</info>.

You can also optionally specify the fields you want to generate in the new
model:

<info>php app/console black:generate:model --model=AcmeBlogBundle:Blog/Post --fields="title:string(255) body:text"</info>

To deactivate the interaction mode, simply use the `--no-interaction` option
without forgetting to pass all needed options:

<info>php app/console black:generate:model --model=AcmeBlogBundle:Blog/Post --fields="title:string(255) body:text" --no-interaction</info>
EOT
        );
    }

    /**
     * @throws \InvalidArgumentException When the bundle doesn't end with Bundle (Example: "Bundle/MySampleBundle")
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        if ($input->isInteractive()) {
            if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        $model = Validators::validateEntityName($input->getOption('model'));
        list($bundle, $model) = $this->parseShortcutNotation($model);
        $fields = $this->parseFields($input->getOption('fields'));

        $dialog->writeSection($output, 'Model generation');

        $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);

        $generator = $this->getGenerator();
        $generator->generate($bundle, $model, array_values($fields));

        $output->writeln('Generating the model code: <info>OK</info>');

        $dialog->writeGeneratorSummary($output, array());
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the Doctrine2 model generator');

        // namespace
        $output->writeln(array(
            '',
            'This command helps you generate Doctrine2 entities.',
            '',
            'First, you need to give the model name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>.',
            ''
        ));

        $bundleNames = array_keys($this->getContainer()->get('kernel')->getBundles());

        while (true) {
            $model = $dialog->askAndValidate($output, $dialog->getQuestion('The Model shortcut name', $input->getOption('model')), array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'), false, $input->getOption('model'), $bundleNames);

            list($bundle, $model) = $this->parseShortcutNotation($model);

            // check reserved words
            if ($this->getGenerator()->isReservedKeyword($model)){
                $output->writeln(sprintf('<bg=red> "%s" is a reserved word</>.', $model));
                continue;
            }

            try {
                $b = $this->getContainer()->get('kernel')->getBundle($bundle);

                if (!file_exists($b->getPath().'/Model/'.str_replace('\\', '/', $model).'.php')) {
                    break;
                }

                $output->writeln(sprintf('<bg=red>Model "%s:%s" already exists</>.', $bundle, $model));
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }
        }
        $input->setOption('model', $bundle.':'.$model);

        // format
        $output->writeln(array(
            '',
            'Determine the format to use for the mapping information.',
            '',
        ));

        // fields
        $input->setOption('fields', $this->addFields($input, $output, $dialog));

        // summary
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf("You are going to generate a \"<info>%s:%s</info>\" Doctrine model", $bundle, $model),
            '',
        ));
    }

    private function parseFields($input)
    {
        if (is_array($input)) {
            return $input;
        }

        $fields = array();
        foreach (explode(' ', $input) as $value) {
            $elements = explode(':', $value);
            $name = $elements[0];
            if (strlen($name)) {
                $type = isset($elements[1]) ? $elements[1] : 'string';
                preg_match_all('/(.*)\((.*)\)/', $type, $matches);
                $type = isset($matches[1][0]) ? $matches[1][0] : $type;
                $length = isset($matches[2][0]) ? $matches[2][0] : null;

                $fields[$name] = array('fieldName' => $name, 'type' => $type, 'length' => $length);
            }
        }

        return $fields;
    }

    private function addFields(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $fields = $this->parseFields($input->getOption('fields'));
        $output->writeln(array(
            '',
            'Instead of starting with a blank model, you can add some fields now.',
            'Note that the primary key will be added automatically (named <comment>id</comment>).',
            '',
        ));
        $output->write('<info>Available types:</info> ');

        $types = array_keys(Type::getTypesMap());
        $count = 20;
        foreach ($types as $i => $type) {
            if ($count > 50) {
                $count = 0;
                $output->writeln('');
            }
            $count += strlen($type);
            $output->write(sprintf('<comment>%s</comment>', $type));
            if (count($types) != $i + 1) {
                $output->write(', ');
            } else {
                $output->write('.');
            }
        }
        $output->writeln('');

        $fieldValidator = function ($type) use ($types) {
            // FIXME: take into account user-defined field types
            if (!in_array($type, $types)) {
                throw new \InvalidArgumentException(sprintf('Invalid type "%s".', $type));
            }

            return $type;
        };

        $lengthValidator = function ($length) {
            if (!$length) {
                return $length;
            }

            $result = filter_var($length, FILTER_VALIDATE_INT, array(
                'options' => array('min_range' => 1)
            ));

            if (false === $result) {
                throw new \InvalidArgumentException(sprintf('Invalid length "%s".', $length));
            }

            return $length;
        };

        while (true) {
            $output->writeln('');
            $generator = $this->getGenerator();
            $columnName = $dialog->askAndValidate($output, $dialog->getQuestion('New field name (press <return> to stop adding fields)', null), function ($name) use ($fields, $generator) {
                if (isset($fields[$name]) || 'id' == $name) {
                    throw new \InvalidArgumentException(sprintf('Field "%s" is already defined.', $name));
                }

                // check reserved words
                if ($generator->isReservedKeyword($name)){
                    throw new \InvalidArgumentException(sprintf('Name "%s" is a reserved word.', $name));
                }

                return $name;
            });
            if (!$columnName) {
                break;
            }

            $defaultType = 'string';

            // try to guess the type by the column name prefix/suffix
            if (substr($columnName, -3) == '_at') {
                $defaultType = 'datetime';
            } elseif (substr($columnName, -3) == '_id') {
                $defaultType = 'integer';
            } elseif (substr($columnName, 0, 3) == 'is_') {
                $defaultType = 'boolean';
            } elseif (substr($columnName, 0, 4) == 'has_') {
                $defaultType = 'boolean';
            }

            $type = $dialog->askAndValidate($output, $dialog->getQuestion('Field type', $defaultType), $fieldValidator, false, $defaultType, $types);

            $data = array('columnName' => $columnName, 'fieldName' => lcfirst(Container::camelize($columnName)), 'type' => $type);

            if ($type == 'string') {
                $data['length'] = $dialog->askAndValidate($output, $dialog->getQuestion('Field length', 255), $lengthValidator, false, 255);
            }

            $fields[$columnName] = $data;
        }

        return $fields;
    }

    protected function createGenerator()
    {
        return new DoctrineModelGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->get('doctrine'));
    }
}

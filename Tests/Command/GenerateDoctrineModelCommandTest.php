<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Black\Bundle\GeneratorBundle\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Black\Bundle\GeneratorBundle\Command\GenerateDoctrineModelCommand;

class GenerateDoctrineModelCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveCommandData
     */
    public function testInteractiveCommand($options, $input, $expected)
    {
        list($entity, $fields) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $entity, $fields)
        ;

        $tester = new CommandTester($this->getCommand($generator, $input));
        $tester->execute($options);
    }

    public function getInteractiveCommandData()
    {
        return array(
            array(array(), "AcmeBlogBundle:Blog/Post\n", array('Blog\\Post', array())),
            array(array('--model' => 'AcmeBlogBundle:Blog/Post'), '', array('Blog\\Post', array())),
            array(array(), "AcmeBlogBundle:Blog/Post\n", array('Blog\\Post', array())),
            array(array(), "AcmeBlogBundle:Blog/Post\ncreated_by\n\n255\ndescription\ntext\n", array('Blog\\Post', array(
                array('fieldName' => 'createdBy', 'type' => 'string', 'length' => 255, 'columnName' => 'created_by'),
                array('fieldName' => 'description', 'type' => 'text', 'columnName' => 'description'),
            ))),
        );
    }

    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($options, $expected)
    {
        list($entity, $fields) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $entity, $fields)
        ;
        $generator
            ->expects($this->any())
            ->method('isReservedKeyword')
            ->will($this->returnValue(false))
        ;

        $tester = new CommandTester($this->getCommand($generator, ''));
        $tester->execute($options, array('interactive' => false));
    }

    public function getNonInteractiveCommandData()
    {
        return array(
            array(array('--model' => 'AcmeBlogBundle:Blog/Post'), array('Blog\\Post', array())),
            array(array('--model' => 'AcmeBlogBundle:Blog/Post', '--fields' => 'created_by:string(255) description:text'), array('Blog\\Post', array(
                array('fieldName' => 'created_by', 'type' => 'string', 'length' => 255),
                array('fieldName' => 'description', 'type' => 'text', 'length' => ''),
            ))),
        );
    }

    protected function getCommand($generator, $input)
    {
        $command = new GenerateDoctrineModelCommand();
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);

        return $command;
    }

    protected function getGenerator()
    {
        // get a noop generator
        return $this
            ->getMockBuilder('Black\Bundle\GeneratorBundle\Generator\DoctrineModelGenerator')
            ->disableOriginalConstructor()
            ->setMethods(array('generate', 'isReservedKeyword'))
            ->getMock()
        ;
    }
}

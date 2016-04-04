<?php
/*
 * This file is part of the ApiGenericBundle package.
 *
 * (c) LeniM <https://github.com/lenim/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeniM\ApiGenericBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Sensio\Bundle\GeneratorBundle\Command\AutoComplete\EntitiesAutoCompleter;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Sensio\Bundle\GeneratorBundle\Command\Validators;

/**
 * @author Martin Leni
 */
class GenerateControllerCommand extends GeneratorCommand
{
    const aActions = array(
        'doctrineMethodAction' => array(
            'name' => 'doctrineMethodAction',
            'route' => '/doctrineMethod',
            'placeholders' => array(),
            'template' => 'SWSMApiBundle:Generic:data.html.twig'
        ),
        'createAction' => array(
            'name' => 'createAction',
            'route' => '/create',
            'placeholders' => array(),
            'template' => 'SWSMApiBundle:Generic:data.html.twig'
        ),
        'updateAction' => array(
            'name' => 'updateAction',
            'route' => '/update',
            'placeholders' => array(),
            'template' => 'SWSMApiBundle:Generic:data.html.twig'
        ),
        'deleteAction' => array(
            'name' => 'deleteAction',
            'route' => '/delete',
            'placeholders' => array(),
            'template' => 'SWSMApiBundle:Generic:data.html.twig'
        ),
        'getAction' => array(
            'name' => 'getAction',
            'route' => '/get',
            'placeholders' => array(),
            'template' => 'SWSMApiBundle:Generic:data.html.twig'
        ),
        'listAction' => array(
            'name' => 'listAction',
            'route' => '/list',
            'placeholders' => array(),
            'template' => 'SWSMApiBundle:Generic:data.html.twig'
        ),
    );
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('lenim:generate:controller')
            ->setDescription('Generates a controller for API')
            ->setDefinition(array(
                new InputOption('controller', '', InputOption::VALUE_REQUIRED, 'The name of the controller to create'),
                new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The name of the controller to create'),
                new InputOption('route-format', '', InputOption::VALUE_REQUIRED, 'The format that is used for the routing (yml, xml, php, annotation)', 'yml'),
                new InputOption('template-format', '', InputOption::VALUE_REQUIRED, 'The format that is used for templating (twig, php)', 'twig'),
            ))
            ->setHelp(<<<EOT
The <info>fos:user:activate</info> command activates a user (so they will be able to log in):
  <info>php app/console fos:user:activate matthieu</info>
EOT
            );
    }
    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);
            if (!$questionHelper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        if (null === $input->getOption('controller')) {
            throw new \RuntimeException('The controller option must be provided.');
        }
        if (null === $input->getOption('entity')) {
            throw new \RuntimeException('The controller option must be provided.');
        }

        list($bundle, $controller) = $this->parseShortcutNotation($input->getOption('controller'));
        if (is_string($bundle)) {
            $bundle = Validators::validateBundleName($bundle);

            try {
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }
        }

        $questionHelper->writeSection($output, 'Controller generation');

        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $controller, $input->getOption('route-format'), $input->getOption('template-format'), self::aActions);

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $questionHelper->writeGeneratorSummary($output, array());

        $output->writeln('controller : '.$input->getOption('controller'));
        $output->writeln('entity : '.$input->getOption('entity'));
        $output->writeln('done');
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the LeniM Api controller generator');

        // namespace
        $output->writeln(array(
            '',
            'Every page, and even sections of a page, are rendered by a <comment>controller</comment>.',
            'This command helps you generate them easily.',
            '',
            'First, you need to give the controller name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>',
            '',
        ));

        $question = new Question($questionHelper->getQuestion('Controller name', $input->getOption('controller')), $input->getOption('controller'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateControllerName'));
        $controller = $questionHelper->ask($input, $output, $question);
        list($bundle, $controller) = $this->parseShortcutNotation($controller);
        $input->setOption('controller', $bundle.':'.$controller);

        // entity targeted
        if ($input->hasArgument('entity') && $input->getArgument('entity') != '') {
            $input->setOption('entity', $input->getArgument('entity'));
        }
        $question = new Question($questionHelper->getQuestion('The Entity shortcut name', $input->getOption('entity')), $input->getOption('entity'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'));
        $autocompleter = new EntitiesAutoCompleter($this->getContainer()->get('doctrine')->getManager());
        $autocompleteEntities = $autocompleter->getSuggestions();
        $question->setAutocompleterValues($autocompleteEntities);
        $entity = $questionHelper->ask($input, $output, $question);

        $input->setOption('entity', $entity);
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
            $metadata = $this->getEntityMetadata($entityClass);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Entity "%s" does not exist in the "%s" bundle. You may have mistyped the bundle name or maybe the entity doesn\'t exist yet (create it first with the "doctrine:generate:entity" command).', $entity, $bundle));
        }

        // routing format
        $defaultFormat = (null !== $input->getOption('route-format') ? $input->getOption('route-format') : 'yml');
        $output->writeln(array(
            '',
            'Determine the format to use for the routing.',
            '',
        ));
        $question = new Question($questionHelper->getQuestion('Routing format (php, xml, yml, annotation)', $defaultFormat), $defaultFormat);
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateFormat'));
        $routeFormat = $questionHelper->ask($input, $output, $question);
        $input->setOption('route-format', $routeFormat);

        // summary
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg-white', true),
            '',
            sprintf('You are going to generate a "<info>%s:%s</info>" controller ', $bundle, $controller),
            sprintf('using the "<info>%s</info>" format for the routing', $routeFormat),
            'for templating',
        ));
    }

    public function parseShortcutNotation($shortcut)
    {
        $entity = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($entity, ':')) {
            throw new \InvalidArgumentException(sprintf('The controller name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Post)', $entity));
        }

        return array(substr($entity, 0, $pos), substr($entity, $pos + 1));
    }

    protected function getEntityMetadata($entity)
    {
        $factory = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        return $factory->getClassMetadata($entity)->getMetadata();
    }

    protected function createGenerator()
    {
        return new ControllerGenerator($this->getContainer()->get('filesystem'));
    }
}
<?php
/*
 * This file is part of the lenim/api-generic-bundle package.
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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Sensio\Bundle\GeneratorBundle\Command\AutoComplete\EntitiesAutoCompleter;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Sensio\Bundle\GeneratorBundle\Command\Validators;

/**
 * @author Martin Leni based on Wouter J <wouter@wouterj.nl> work for Symfony package
 */
class GenerateControllerCommand extends GeneratorCommand
{
    public $entity = null;
    public $entityInfos = null;
    public $aActions = array(
        'doctrineMethodAction' => array(
            'name' => 'doctrineMethodAction',
            'route' => '/filter/{propertie}/{value}.{_format}',
            'placeholders' => array('request', 'propertie', 'value'),
            'parent' => 'doctrineMethod',
            'template' => 'SWSMApiBundle:Generic:data.html.twig',
            'methods' => 'GET, HEAD',
            'pathextra' => ', _format: ~',
            'route_require' => array('_format' => 'json|xml|html')
        ),
        'createAction' => array(
            'name' => 'createAction',
            'route' => '/create.{_format}',
            'placeholders' => array('request'),
            'parent' => 'crudCreate',
            'template' => 'SWSMApiBundle:Generic:data.html.twig',
            'methods' => 'POST',
            'pathextra' => ', _format: ~',
            'route_require' => array('_format' => 'json|xml|html')
        ),
        'updateAction' => array(
            'name' => 'updateAction',
            'route' => '/{id}/update.{_format}',
            'placeholders' => array('request', 'id'),
            'parent' => 'crudUpdate',
            'template' => 'SWSMApiBundle:Generic:data.html.twig',
            'methods' => 'PUT',
            'pathextra' => ', _format: ~',
            'route_require' => array('id' => '\d+', '_format' => 'json|xml|html')
        ),
        'deleteAction' => array(
            'name' => 'deleteAction',
            'route' => '/{id}/delete.{_format}',
            'placeholders' => array('request', 'id'),
            'parent' => 'crudDelete',
            'template' => 'SWSMApiBundle:Generic:data.html.twig',
            'methods' => 'DELETE',
            'pathextra' => ', _format: ~',
            'route_require' => array('id' => '\d+', '_format' => 'json|xml|html')
        ),
        'getAction' => array(
            'name' => 'getAction',
            'route' => '/{id}.{_format}',
            'placeholders' => array('request', 'id'),
            'parent' => 'crudGet',
            'template' => 'SWSMApiBundle:Generic:data.html.twig',
            'methods' => 'GET, HEAD',
            'pathextra' => ', _format: ~',
            'route_require' => array('id' => '\d+', '_format' => 'json|xml|html')
        ),
        'listAction' => array(
            'name' => 'listAction',
            'route' => '/list.{_format}',
            'placeholders' => array('request'),
            'parent' => 'crudList',
            'template' => 'SWSMApiBundle:Generic:data.html.twig',
            'methods' => 'GET, HEAD',
            'pathextra' => ', _format: ~',
            'route_require' => array('_format' => 'json|xml|html')
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

        $this->generateActions();

        $aParams = array(
            'action' => $this->aActions,
            'entityInfos' => array(
                'repository' => (isset($this->entityInfos->customRepositoryClassName) ? $this->entityInfos->customRepositoryClassName : $this->entity),
                'entityFullName' => $input->getOption('entity'),
                'entity' => $this->entityInfos->name,
                'form' => str_replace('\Entity\\', '\Form\\', $this->entityInfos->name).'Type',
                'enableDocumentation' => $this->nelmioEnabled(),
                'fields' => $this->entityInfos
            ));

        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $controller, $input->getOption('route-format'), $input->getOption('template-format'), $aParams);

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $questionHelper->writeGeneratorSummary($output, array());
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

        // $question = new Question($questionHelper->getQuestion('Controller name', 'SWSMApiBundle:Test'), 'SWSMApiBundle:Test');
        $question = new Question($questionHelper->getQuestion('Controller name', $input->getOption('controller')), $input->getOption('controller'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateControllerName'));
        $controller = $questionHelper->ask($input, $output, $question);
        list($bundle, $controller) = $this->parseShortcutNotation($controller);
        $input->setOption('controller', $bundle.':'.$controller);

        // entity targeted
        if ($input->hasArgument('entity') && $input->getArgument('entity') != '') {
            $input->setOption('entity', $input->getArgument('entity'));
        }


        // $question = new Question($questionHelper->getQuestion('The Entity shortcut name', 'SWSMCalculationBundle:B2B\Washroom'), 'SWSMCalculationBundle:B2B\Washroom');
        $question = new Question($questionHelper->getQuestion('The Entity shortcut name', $input->getOption('entity')), $input->getOption('entity'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'));
        $autocompleter = new EntitiesAutoCompleter($this->getContainer()->get('doctrine')->getManager());
        $autocompleteEntities = $autocompleter->getSuggestions();
        $question->setAutocompleterValues($autocompleteEntities);
        $entity = $questionHelper->ask($input, $output, $question);

        $input->setOption('entity', $entity);
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        $this->entity = $entity;

        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
            $metadata = $this->getEntityMetadata($entityClass);
            $this->entityInfos = $metadata[0];
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

    protected function generateActions(){
        $this->aActions['doctrineMethodAction']['description_short'] = 'Filtered list';
        $this->aActions['createAction']['description_short']      = 'Create';
        $this->aActions['updateAction']['description_short']      = 'Update';
        $this->aActions['deleteAction']['description_short']      = 'Delete';
        $this->aActions['getAction']['description_short']         = 'Get One';
        $this->aActions['listAction']['description_short']        = 'List all';

        $this->aActions['doctrineMethodAction']['description'] = 'Enable to pull a list of '.$this->entity.' in filtering the results by one of its properties';
        $this->aActions['createAction']['description']      = 'Enable to Create a '.$this->entity.'. It ll return or this element itself, or an error';
        $this->aActions['updateAction']['description']      = 'Enable to Update a existing '.$this->entity.', tagged by it\'s id. It ll return or this element itself, or an error';
        $this->aActions['deleteAction']['description']      = 'Enable to delete a '.$this->entity.' this entities will be permanently removed and no roll back is avaible !';
        $this->aActions['getAction']['description']         = 'Enable to pull a '.$this->entity.' by id, the full tree will be returned including it\'s childrens';
        $this->aActions['listAction']['description']        = 'Enable to pull all the existing '.$this->entity.', all '.$this->entity.'\'s full tree will be returned including its childs';

        if($this->nelmioEnabled())
        {
            $aEntityInfos = array();

            foreach($this->entityInfos->fieldMappings as $sField => $aField)
            {
                if(in_array($aField['type'], array('smallint', 'integer', 'bigint')))
                {
                    $aEntityInfos[$sField] = array(
                        'dataType' => 'integer',
                        'description' => $sField,
                        'regex' => '\d+'
                    );
                }
                elseif(in_array($aField['type'], array('decimal', 'float', '')))
                {
                    $aEntityInfos[$sField] = array(
                        'dataType' => 'decimal',
                        'description' => $sField,
                        'regex' => '\d*\.?\d*'
                    );
                }
                elseif(in_array($aField['type'], array('boolean')))
                {
                    $aEntityInfos[$sField] = array(
                        'dataType' => 'boolean',
                        'description' => $sField,
                        'regex' => '0|1'
                    );
                }
                elseif(in_array($aField['type'], array('date')))
                {
                    $aEntityInfos[$sField] = array(
                        'dataType' => 'date',
                        'description' => $sField,
                        'regex' => 'Y/m/d',
                    );
                }
                elseif(in_array($aField['type'], array('datetime')))
                {
                    $aEntityInfos[$sField] = array(
                        'dataType' => 'datetime',
                        'description' => $sField,
                        // 'regex' => '(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]|(?:Jan|Mar|May|Jul|Aug|Oct|Dec)))\1|(?:(?:29|30)(\/|-|\.)(?:0?[1,3-9]|1[0-2]|(?:Jan|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec))\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)(?:0?2|(?:Feb))\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9]|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep))|(?:1[0-2]|(?:Oct|Nov|Dec)))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})'
                        'regex' => 'Y/m/d H:i:s'
                    );
                }
                else
                {
                    $aEntityInfos[$sField] = array(
                        'dataType' => 'text',
                        'description' => $sField,
                        'regex' => '.*?'
                    );
                }
                if($aField['nullable'] == false)
                {
                     $aEntityInfos[$sField]['required'] = true;
                }
                $aEntityInfos[$sField]['name'] = $sField;
                $aEntityInfos[$sField]['columnNames'] = $$aField['nullable'] = $this->entityInfos->columnNames[$sField];
            }

            // Generate nelmio documentation
            foreach($this->aActions as $k => $v)
            {
                // $this->aActions[$k]['route'] = strtolower(str_replace('\\', '', $this->entity)).$this->aActions[$k]['route'];
                $this->aActions[$k]['nelmio'] = array(
                    'resource' => 'true',
                    'description' => $this->aActions[$k]['description_short'],
                    // 'statusCodes' =>array(
                    //     '400' => 'Validation failed.',
                    //     '200' => 'OK!',
                    // ),
                    // 'responseMap' => array(

                    // )
                );
                if($k == 'doctrineMethodAction')
                {
                    $this->aActions[$k]['nelmio']['output'] = 'array';
                    $this->aActions[$k]['nelmio']['requirements'] = array(
                        'propertie' => array(
                            'name'        => 'propertie',
                            'columnNames' => 'propertie',
                            'dataType'    => 'string',
                            'description' => 'Propertie you want to use as filter, camel case is handled'
                        ),
                        'value' => array(
                            'name'        => 'value',
                            'columnNames' => 'value',
                            'dataType'    => 'string',
                            'description' => 'Value you want to use as filter'
                        )
                    );
                    $this->aActions[$k]['route_require']['propertie'] = implode('|', array_values($this->entityInfos->fieldNames));
                }
                if($k == 'createAction' || $k == 'updateAction')
                {
                    $a = $aEntityInfos;
                    unset($a['id']);
                    $this->aActions[$k]['nelmio']['output'] = $this->entityInfos->name;
                    $this->aActions[$k]['nelmio']['parameters'] = $a;
                }
                if($k == 'deleteAction' || $k == 'updateAction')
                {
                    $this->aActions[$k]['nelmio']['requirements'] = array(
                        'id' => array(
                            'name' => 'id',
                            'columnNames' => 'id',
                            'dataType' => 'integer',
                            'description' => 'Id of the '.$this->entity.' you want to target'
                        )
                    );
                }
                if($k == 'doctrineMethodAction' || $k == 'listAction')
                {
                    if(!isset($this->aActions[$k]['nelmio']['filters'])) $this->aActions[$k]['nelmio']['filters'] = array();
                    $this->aActions[$k]['nelmio']['filters']['page'] = array(
                        'name' => 'page',
                        'dataType' => 'integer',
                        'description' => 'Page number, this parameter is directly related to the other parameter limit'
                    );
                    $this->aActions[$k]['nelmio']['filters']['offset'] = array(
                        'name' => 'offset',
                        'dataType' => 'integer',
                        'description' => 'Number of the first element, allows you to build your own pagnation system'
                    );
                    $this->aActions[$k]['nelmio']['filters']['limit'] = array(
                        'name' => 'limit',
                        'dataType' => 'integer',
                        'description' => 'Maximum number of elements you want to display'
                    );
                    $this->aActions[$k]['nelmio']['filters']['order'] = array(
                        'name' => 'order',
                        'dataType' => 'mixed',
                        'description' => 'field you want to order by, default is id ASC. You can also send an array with the field as key and ASC|DESC as value'
                    );
                }
            }
        }
    }

    protected function nelmioEnabled()
    {
        $aBundles = $this->getContainer()->getParameter('kernel.bundles');
        return in_array('Nelmio\ApiDocBundle\NelmioApiDocBundle', $aBundles);
    }
}
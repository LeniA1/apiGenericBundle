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

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand as GeneratorCommandParent;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;

/**
 * Base class for generator commands.
 *
 * @author Martin Leni based on Fabien Potencier <fabien@symfony.com> work for Symfony package
 */
abstract class GeneratorCommand extends GeneratorCommandParent
{
    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $skeletonDirs = array();

        if (isset($bundle) && is_dir($dir = $bundle->getPath().'/Resources/SensioGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir().'/Resources/SensioGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $skeletonDirs[] = __DIR__.'/../Resources/skeleton';
        $skeletonDirs[] = __DIR__.'/../Resources';

        return $skeletonDirs;
    }
}

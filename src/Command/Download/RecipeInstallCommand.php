<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\CodeObject\Resolver\ClassResolver;
use DevCoding\Jss\Easy\Object\Recipe\AbstractRecipe;
use DevCoding\Mac\Command\AbstractMacConsole;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs the given application using a recipe class.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Command\Download
 */
class RecipeInstallCommand extends AbstractMacConsole
{
  protected function configure()
  {
    $this->setName('install:recipe')
         ->setAliases(['install:configured'])
         ->addArgument('name', InputArgument::REQUIRED, 'The recipe name for the application.')
    ;

    parent::configure();
  }

  protected function isAllowUserOption()
  {
    return false;
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   * @throws \ReflectionException
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $name = $this->io()->getArgument('name');

    $this->io()->msg('Retrieving Configuration', 50);
    $alternates = [];
    if ($Installer = $this->getRecipe($name, $alternates))
    {
      $this->successbg($Installer->getName());

      // Find Installed Version
      $this->io()->msg('Finding Installed Version', 50);
      $installed = $Installer->getInstalledVersion();
      $this->successbg($installed ?? 'NONE');

      // Find Current Version
      $this->io()->msg('Finding Current Version', 50);
      $current = $Installer->getCurrentVersion();
      if (false === $current || $current)
      {
        $this->successbg($current ?: 'SUCCESS');
      }
      else
      {
        $this->errorbg('ERROR');

        return self::EXIT_ERROR;
      }

      // Install if Needed
      if (!$Installer->isCurrent())
      {
        if ($command = $this->getCommand($Installer))
        {
          if ($args = $this->getArguments($Installer))
          {
            return $command->run($args, $output);
          }
        }
      }
      else
      {
        return self::EXIT_SUCCESS;
      }
    }
    else
    {
      $this->errorbg('FAILED');

      $msg = 'Could not find a configuration for "'.$name.'".';

      if (!empty($alternates))
      {
        $msg .= ' Did you mean: '.implode(', ', $alternates).'?';
      }

      $this->io()->errorblk($msg);
    }

    return self::EXIT_ERROR;
  }

  protected function getArguments(AbstractRecipe $installer)
  {
    if ($type = $installer->getInstallerType())
    {
      if ('dmg' === $type || 'pkg' === $type || 'zip' === $type)
      {
        $current   = $installer->getCurrentVersion();
        $installed = $installer->getInstalledVersion();

        $args = ['destination' => $installer->getPath(), 'url' => $installer->getDownloadUrl()];

        if ($current)
        {
          $args['--target'] = $current;
        }

        if ($installed)
        {
          $args['--installed'] = $installed;
        }

        return new ArrayInput($args);
      }
    }

    return null;
  }

  /**
   * @param AbstractRecipe $installer
   *
   * @return Command|null
   */
  protected function getCommand(AbstractRecipe $installer)
  {
    if ($type = $installer->getInstallerType())
    {
      if ('dmg' === $type)
      {
        return $this->getApplication()->find('install:dmg');
      }
      elseif ('pkg' === $type)
      {
        return $this->getApplication()->find('install:pkg');
      }
      elseif ('zip' === $type)
      {
        return $this->getApplication()->find('install:zip');
      }
    }

    return null;
  }

  /**
   * @param string $name
   *
   * @return AbstractRecipe|null
   *
   * @throws \ReflectionException
   */
  protected function getRecipe($name, &$alternates)
  {
    return ($fqcn = $this->getClass($name, $alternates)) ? new $fqcn($this->getDevice()) : null;
  }

  /**
   * @param string $name
   *
   * @return string|null
   *
   * @throws \ReflectionException
   */
  protected function getClass($name, &$alternates = [])
  {
    $class = str_replace([' ', '_', '-'], '', ucwords($name, ' _-'));

    $rClass = new \ReflectionClass(AbstractRecipe::class);
    $nspace = $rClass->getNamespaceName();
    $fqcn   = $nspace.'\\'.$class;

    if (class_exists($fqcn))
    {
      return $fqcn;
    }

    if ($configured = (new ClassResolver([$nspace]))->all())
    {
      foreach ($configured as $config)
      {
        $rClass = new \ReflectionClass($config);
        $cClass = $rClass->getShortName();
        $lev    = levenshtein($class, $cClass);
        if ($lev <= \strlen($class) / 3 || false !== strpos($cClass, $class))
        {
          $alternates[] = strtolower(preg_replace('~(?<=\\w)([A-Z])~', '-$1', $cClass));
        }
      }
    }

    return null;
  }

  protected function successbg($msg)
  {
    $this->io()->write('[')->success(strtoupper($msg))->writeln(']');

    return $this;
  }

  protected function errorbg($msg)
  {
    $this->io()->write('[')->error(strtoupper($msg))->writeln(']');

    return $this;
  }
}

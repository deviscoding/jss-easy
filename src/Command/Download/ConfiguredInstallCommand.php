<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Object\Installer\BaseInstaller;
use DevCoding\Mac\Command\AbstractMacConsole;
use DevCoding\Mac\Objects\SemanticVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 * @package DevCoding\Jss\Easy\Command\Download
 */
class ConfiguredInstallCommand extends AbstractMacConsole
{
  protected function configure()
  {
    $this->setName('install:configured')
         ->addArgument('name', InputArgument::REQUIRED, 'The configured application name.')
    ;

    parent::configure();
  }

  protected function isAllowUserOption()
  {
    return false;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $name = $this->io()->getArgument('name');

    $this->io()->msg('Retrieving Configuration', 50);
    if ($Installer = $this->getInstaller($name))
    {
      $this->successbg('SUCCESS');
      $this->io()->msg('Finding Current Version', 50);
      $version = $Installer->getCurrentVersion();
      if (false === $version || $version)
      {
        if ($version)
        {
          $version = (new SemanticVersion($version))->__toString();
        }

        $this->successbg($version ?: 'SUCCESS');

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
        $this->errorbg('FAILED');

        return self::EXIT_ERROR;
      }
    }

    return self::EXIT_ERROR;
  }

  protected function getArguments(BaseInstaller $installer)
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
   * @param BaseInstaller $installer
   *
   * @return Command|null
   */
  protected function getCommand(BaseInstaller $installer)
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
   * @param $name
   *
   * @return BaseInstaller|null
   */
  protected function getInstaller($name)
  {
    return ($fqcn = $this->getClass($name)) ? new $fqcn($this->getDevice()) : null;
  }

  protected function getClass($name)
  {
    $class = str_replace([' ', '_', '-'], '', ucwords($name, ' _-'));

    $rClass = new \ReflectionClass(BaseInstaller::class);
    $nspace = $rClass->getNamespaceName();
    $fqcn   = $nspace.'\\'.$class;

    return $fqcn;
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

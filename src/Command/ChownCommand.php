<?php

namespace DevCoding\Jss\Easy\Command;

use DevCoding\Mac\Command\AbstractMacConsole;
use DevCoding\Mac\Objects\MacUser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChownCommand extends AbstractMacConsole
{
  protected function configure()
  {
    parent::configure();

    $this->setName('chown:user')
         ->addArgument('file', InputArgument::REQUIRED)
         ->addOption('console', null, InputOption::VALUE_NONE, 'Uses the user logged into the GUI.')
         ->addOption('recursive', 'R', InputOption::VALUE_NONE, 'Performs the change recursively.')
         ->setDescription('Sets the owner and group of the given file to match the owner of the given user\'s home directory.')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $file  = $this->io()->getArgument('file');
    $width = 50;

    $this->io()->blankln()->msg('Checking for User', $width);
    if (!$this->io()->getOption('user') && !$this->io()->getOption('console'))
    {
      return self::EXIT_ERROR;
    }

    if ($this->io()->getOption('console'))
    {
      try
      {
        $MacUser = MacUser::fromConsole(true);
      }
      catch (\Exception $e)
      {
        $this->io()->errorln('[ERROR]');
        $this->io()->write('  Could not determine the logged in user. Perhaps no one is logged in?', null, null, OutputInterface::VERBOSITY_VERBOSE);
      }
    }
    elseif (!empty($this->_user))
    {
      try
      {
        $MacUser = MacUser::fromString($this->_user);
      }
      catch (\Exception $e)
      {
        $this->io()->errorln('[ERROR]');
        $this->io()->write('  Invalid username format given. How about something alphanumeric?', null, null, OutputInterface::VERBOSITY_VERBOSE);
      }
    }
    else
    {
      $this->io()->error('[ERROR]');
      $this->io()->write('  Either the --user option or the --console option must be used.');
    }

    if (!isset($MacUser) || !$MacUser instanceof MacUser)
    {
      return self::EXIT_ERROR;
    }
    else
    {
      $this->io()->successln('[SUCCESS]');
    }

    $this->io()->msg('Finding Owner & Group', 50);
    $uDir = $MacUser->getDir();
    if (is_dir($uDir))
    {
      $oId = @fileowner($uDir);
      $own = $oId ? @posix_getpwuid($oId) : null;
      $gId = @filegroup($uDir);
      $grp = $gId ? @posix_getgrgid($gId) : null;

      if (!$own || !$oId)
      {
        $this->io()->errorln('[ERROR]');
        if (!$oId)
        {
          $this->io()->write('  Could not determine UID of owner of: '.$uDir, null, null, OutputInterface::VERBOSITY_VERBOSE);
        }
        else
        {
          $this->io()->write('  Could not determine owner of: '.$uDir, null, null, OutputInterface::VERBOSITY_VERBOSE);
        }
      }
      else
      {
        $this->io()->successln('[SUCCESS]');
      }
    }
    else
    {
      $this->io()->errorln('[ERROR]');
      $this->io()->write('  Could not locate user directory.', null, null, OutputInterface::VERBOSITY_VERBOSE);

      return self::EXIT_ERROR;
    }

    $type = $this->isRecursive() ? 'directory' : 'file';
    $this->io()->msg('Verifying '.ucfirst($type), $width);
    if (!file_exists($file))
    {
      $this->io()->errorln('[ERROR]');
      $this->io()->write('  The given '.$type.' does not exist!');

      return self::EXIT_ERROR;
    }
    else
    {
      $this->io()->successln('[SUCCESS]');
    }

    $owner = [$own, $oId];
    $group = [$grp, $gId];
    if ($this->isRecursive())
    {
      $hasError = false;
      $this->io()->msg('Setting Ownership Recursively', 50);
      $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($file));
      foreach ($iterator as $item)
      {
        if ('..' !== substr($item, -2, 2) && '.' !== substr($item, -1, 1) )
        {
          if (!$this->chown($item, $owner))
          {
            if (!$hasError)
            {
              $this->io()->errorln('[ERROR]');
              $hasError = true;
            }

            $this->io()->writeln('  Owner Error: '.$item, null, null, OutputInterface::VERBOSITY_VERBOSE);
          }

          if (!$this->chgrp($item, $group))
          {
            if (!$hasError)
            {
              $this->io()->errorln('[ERROR]');
              $hasError = true;
            }

            $this->io()->writeln('  Group Error: '.$item, null, null, OutputInterface::VERBOSITY_VERBOSE);
          }
        }
      }

      if (!$hasError)
      {
        $this->io()->successln('[SUCCESS]');
      }
      else
      {
        $this->io()->blankln();

        return self::EXIT_ERROR;
      }
    }
    else
    {
      $this->io()->msg('Setting Ownership', 50);
      $rvOwner = $this->chown($file, $owner);
      $rvGroup = $this->chgrp($file, $group);

      if (!$rvGroup || !$rvOwner)
      {
        $this->io()->errorln('[ERROR]')->blankln();

        return self::EXIT_ERROR;
      }
      else
      {
        $this->io()->successln('[SUCCESS]');
      }
    }

    $this->io()->blankln();

    return self::EXIT_SUCCESS;
  }

  protected function chown($file, $users)
  {
    if ($this->isFile($file) || $this->isDir($file))
    {
      $users = is_array($users) ? $users : explode(',', $users);
      foreach ($users as $user)
      {
        if (@chown($file, $user))
        {
          return true;
        }
      }

      return false;
    }

    return true;
  }

  protected function chgrp($file, $groups)
  {
    if ($this->isFile($file) || $this->isDir($file))
    {
      $groups = is_array($groups) ? $groups : explode(',', $groups);
      foreach ($groups as $group)
      {
        if (@chgrp($file, $group))
        {
          return true;
        }
      }

      return false;
    }

    return true;
  }

  protected function isFile($file)
  {
    if (is_link($file) || is_dir($file) || !is_file($file))
    {
      return false;
    }

    return realpath($file) == $file;
  }

  protected function isDir($file)
  {
    if (is_link($file) || !is_dir($file))
    {
      return false;
    }

    return realpath($file) == $file;
  }

  protected function isRecursive()
  {
    return $this->io()->getOption('recursive');
  }

  protected function isAllowUserOption()
  {
    return true;
  }
}

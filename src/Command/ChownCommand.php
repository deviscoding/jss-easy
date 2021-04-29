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
         ->addOption('console', InputOption::VALUE_NONE)
         ->setDescription('Sets the owner and group of the given file to match the owner of the given user\'s home directory.')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $file  = $this->io()->getArgument('file');
    $width = 50;

    if (!$this->io()->getOption('user') && !$this->io()->getOption('console'))
    {
      $this->io()->errorblk('Either the --user option or the --console option must be used.');

      return self::EXIT_ERROR;
    }

    if (file_exists($file))
    {
      try
      {
        if (!$this->io()->getOption('console'))
        {
          $MacUser = $this->getUser();
        }
        else
        {
          $MacUser = MacUser::fromConsole(true);
        }

        $uDir = $MacUser->getDir();
        if (is_dir($uDir))
        {
          $oId = @fileowner($uDir);
          $own = $oId ? @posix_getpwuid($oId) : null;
          $gId = @filegroup($uDir);
          $grp = $gId ? @posix_getgrgid($gId) : null;
        }
        else
        {
          $this->io()->errorblk('Could not locate the user directory.');
          return self::EXIT_ERROR;
        }
      }
      catch (\Exception $e)
      {
        $this->io()->errorblk('Could not retrieve a User ID for the user.');
        return self::EXIT_ERROR;
      }
    }
    else
    {
      $this->io()->errorblk('The given file does not exist.');
      return self::EXIT_ERROR;
    }

    $this->io()->msg('Setting Ownership',$width);
    $setOwnerA = isset($own) && @chown($file,$own);
    $setOwnerB = !$setOwnerA && isset($oId) && @chown($file, $oId);
    if (!$setOwnerA && !$setOwnerB)
    {
      $this->io()->errorln('ERROR');

      if (!$own)
      {
        if (!$oId)
        {
          $this->io()->write('  Could not determine UID of owner of: ' . $uDir,null,null,OutputInterface::VERBOSITY_VERBOSE);
        }
        else
        {
          $this->io()->write('  Could not determine owner of: ' . $uDir,null,null,OutputInterface::VERBOSITY_VERBOSE);
          $this->io()->write(sprintf('  Could not set owner of "%s" to "%s": ',$file,$oId),null,null,OutputInterface::VERBOSITY_VERBOSE);
        }
      }
      else
      {
        $this->io()->write(sprintf('  Could not set owner of "%s" to "%s": ',$file,$own),null,null,OutputInterface::VERBOSITY_VERBOSE);
      }

      return self::EXIT_ERROR;
    }
    else
    {
      $this->io()->successln('SUCCESS');
    }

    $this->io()->msg('Setting Group',$width);
    $setGroupA = isset($grp) && @chgrp($file,$grp);
    $setGroupB = !$setGroupA && isset($gId) && @chgrp($file, $gId);
    if (!$setGroupA && !$setGroupB)
    {
      $this->io()->errorln('ERROR');

      if (!$grp)
      {
        if (!$gId)
        {
          $this->io()->write('  Could not determine UID of group of: ' . $uDir,null,null,OutputInterface::VERBOSITY_VERBOSE);
        }
        else
        {
          $this->io()->write('  Could not determine group of: ' . $uDir,null,null,OutputInterface::VERBOSITY_VERBOSE);
          $this->io()->write(sprintf('  Could not set group of "%s" to "%s": ',$file,$gId),null,null,OutputInterface::VERBOSITY_VERBOSE);
        }
      }
      else
      {
        $this->io()->write(sprintf('  Could not set group of "%s" to "%s": ',$file,$grp),null,null,OutputInterface::VERBOSITY_VERBOSE);
      }

      return self::EXIT_ERROR;
    }
    else
    {
      $this->io()->successln('SUCCESS');
    }

    return self::EXIT_SUCCESS;
  }

  protected function isAllowUserOption()
  {
    return true;
  }
}
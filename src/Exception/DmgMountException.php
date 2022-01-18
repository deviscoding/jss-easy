<?php

namespace DevCoding\Jss\Easy\Exception;

class DmgMountException extends \Exception
{
  public function __construct($dmgFile, $msg = null, $code = 0, \Throwable $previous = null)
  {
    $complete = sprintf('The DMG file "%s" could not be mounted.', $dmgFile);

    if (isset($msg))
    {
      $complete .= ' '.$msg;
    }

    parent::__construct($complete, $code, $previous);
  }

}

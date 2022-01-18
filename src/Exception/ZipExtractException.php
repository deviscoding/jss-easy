<?php

namespace DevCoding\Jss\Easy\Exception;

class ZipExtractException extends \Exception
{
  public function __construct($zipFile, $msg = null, $code = 0, \Throwable $previous = null)
  {
    $complete = sprintf('The ZIP file "%s" could not be unzipped.', $zipFile);

    if (isset($msg))
    {
      $complete .= ' '.$msg;
    }

    parent::__construct($complete, $code, $previous);
  }
}

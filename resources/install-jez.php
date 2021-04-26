#!/usr/bin/env php

<?php

// This script can be used in a manually triggered policy to install
// the JEZ executable.  The trigger for the policy should match the
// name used in the dependency line of your scripts.  See the example
// scripts for details.

// region //////////////////////////////////////////////// Variables

// Customized Variables
$ghUser = 'deviscoding';
$ghRepo = 'jss-easy';
$ghFile = 'jez.phar';
$binDir = '/usr/local/bin';
$name   = 'Jez';

// Derived Variables
$fileETag    = sprintf('/tmp/curl/github.%s.%s.etag', $ghUser, $ghRepo);
$fileVersion = sprintf('/tmp/curl/github.%s.%s.ver', $ghUser, $ghRepo);
$urlRelease  = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $ghUser, $ghRepo);
$urlDownload = sprintf('https://github.com/%s/%s/releases/download', $ghUser, $ghRepo);
$binFile     = pathinfo($ghFile, PATHINFO_FILENAME);
$binPath     = sprintf('%s/%s', $binDir, $binFile);
$exitCode    = 0;
$curr        = null;
$inst        = null;

// endregion ///////////////////////////////////////////// Variables

// region //////////////////////////////////////////////// Functions

function getDownload($url, $file)
{
  $fp = fopen($file, 'w+');
  $ch = curl_init(str_replace(' ', '%20', $url));
  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  fclose($fp);

  if (200 == $code)
  {
    chmod($file, 0755);

    return true;
  }
  else
  {
    return false;
  }
}

function getUrl($url, $default = null, $etag = null)
{
  $ch = curl_init($url);
  $rq = [];
  if ($etag)
  {
    $rq[] = sprintf('If-None-Match: %s', $etag);
  }

  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $rq);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Jamf Fetcher');

  $resp = curl_exec($ch);
  $len  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $head = substr($resp, 0, $len);
  $body = substr($resp, $len);
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  $header = [];
  foreach (explode(PHP_EOL, $head) as $line)
  {
    $parts = explode(':', $line);

    if (count($parts) > 1)
    {
      $key = trim($parts[0]);
      $val = trim($parts[1]);

      $header[$key] = $val;
    }
  }

  curl_close($ch);

  if (304 == $code)
  {
    return ['headers' => $header, 'body' => $default, 'cached' => true];
  }
  else
  {
    return ['headers' => $header, 'body' => $body, 'cached' => false];
  }
}

$colors = shell_exec('tput colors 2>/dev/null') ?: null;
function println($content, $code = 0)
{
  global $colors;
  if (!empty($colors) && !empty($code) && !empty($content))
  {
    $text = sprintf('printf "[";tput setaf %s;printf "%s" "%s";tput sgr0;echo "]"', $code, '%s', $content);
    passthru($text);
  }
  else
  {
    echo '['.$content."]\n";
  }
}

function successln($content)
{
  println($content, 2);
}

function errorln($content)
{
  println($content, 1);
}

function write($content, $width = 50)
{
  echo str_pad($content, $width);
}

// endregion ///////////////////////////////////////////// Functions

// region //////////////////////////////////////////////// Main Code

function getCurrentVersion($bin, $verFlag)
{
  if (file_exists($bin))
  {
    $version = shell_exec(sprintf('%s %s 2>/dev/null', $bin, $verFlag));
    $parts   = explode(' ', $version);

    foreach ($parts as $part)
    {
      if (preg_match('#([0-9.]+)#', $part, $matches))
      {
        return 'v'.$matches[1];
      }
    }
  }

  return null;
}

echo "\r\n";
write(sprintf('Current %s Version...', $name));
if (file_exists($fileETag) && file_exists($fileVersion))
{
  $etag = file_get_contents($fileETag);
  $body = file_get_contents($fileVersion);
  $resp = getUrl($urlRelease, $body, $etag);
}
else
{
  $resp = getUrl($urlRelease);

  if (!empty($resp['headers']['etag']))
  {
    if (!is_dir(dirname($fileETag)))
    {
      mkdir(dirname($fileETag), 0777, true);
    }

    file_put_contents($fileETag, $resp['headers']['etag']);
  }
}

if (!empty($resp['body']))
{
  $parts = json_decode(trim($resp['body']), true);
  $curr  = !empty($parts['tag_name']) ? $parts['tag_name'] : null;

  if ($curr)
  {
    if (!is_dir(dirname($fileVersion)))
    {
      mkdir(dirname($fileVersion), 0777, true);
    }

    file_put_contents($fileVersion, $resp['body']);
  }
}

if (empty($curr))
{
  errorln('ERROR');
  $exitCode = 1;
}
else
{
  successln($curr);
  write(sprintf('Installed %s Version...', $name));
  if ($inst = getCurrentVersion($binPath, '-V'))
  {
    successln($inst);
  }
  else
  {
    successln('None');
  }
}

if (0 === $exitCode && $curr != $inst)
{
  write('Downloading New Version...');
  $download = sprintf('%s/%s/%s', $urlDownload, $curr, $ghFile);
  if ($result = getDownload($download, $binPath))
  {
    successln('SUCCESS');
  }
  else
  {
    errorln('FAILED');
    $exitCode = 1;
  }

  write('Verifying new Version...');
  $new = getCurrentVersion($binPath, '-V');
  if ($new && $new == $curr)
  {
    successln($new);
  }
  else
  {
    errorln(empty($new) ? 'Unknown' : $new);
    $exitCode = 1;
  }
}

echo "\r\n";
exit($exitCode);

// endregion ///////////////////////////////////////////// Main Code

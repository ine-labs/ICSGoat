<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'v2.10.3RC1',
    'version' => '2.10.3.0-RC1',
    'aliases' => 
    array (
    ),
    'reference' => 'af258dfbb566bdfd429650d2e395c5a0f4850c97',
    'name' => 'owncloud/firewall',
  ),
  'versions' => 
  array (
    'hoa/compiler' => 
    array (
      'pretty_version' => '3.17.08.08',
      'version' => '3.17.08.08',
      'aliases' => 
      array (
      ),
      'reference' => 'aa09caf0bf28adae6654ca6ee415ee2f522672de',
    ),
    'hoa/consistency' => 
    array (
      'pretty_version' => '1.17.05.02',
      'version' => '1.17.05.02',
      'aliases' => 
      array (
      ),
      'reference' => 'fd7d0adc82410507f332516faf655b6ed22e4c2f',
    ),
    'hoa/event' => 
    array (
      'pretty_version' => '1.17.01.13',
      'version' => '1.17.01.13',
      'aliases' => 
      array (
      ),
      'reference' => '6c0060dced212ffa3af0e34bb46624f990b29c54',
    ),
    'hoa/exception' => 
    array (
      'pretty_version' => '1.17.01.16',
      'version' => '1.17.01.16',
      'aliases' => 
      array (
      ),
      'reference' => '091727d46420a3d7468ef0595651488bfc3a458f',
    ),
    'hoa/file' => 
    array (
      'pretty_version' => '1.17.07.11',
      'version' => '1.17.07.11',
      'aliases' => 
      array (
      ),
      'reference' => '35cb979b779bc54918d2f9a4e02ed6c7a1fa67ca',
    ),
    'hoa/iterator' => 
    array (
      'pretty_version' => '2.17.01.10',
      'version' => '2.17.01.10',
      'aliases' => 
      array (
      ),
      'reference' => 'd1120ba09cb4ccd049c86d10058ab94af245f0cc',
    ),
    'hoa/math' => 
    array (
      'pretty_version' => '1.17.05.16',
      'version' => '1.17.05.16',
      'aliases' => 
      array (
      ),
      'reference' => '7150785d30f5d565704912116a462e9f5bc83a0c',
    ),
    'hoa/protocol' => 
    array (
      'pretty_version' => '1.17.01.14',
      'version' => '1.17.01.14',
      'aliases' => 
      array (
      ),
      'reference' => '5c2cf972151c45f373230da170ea015deecf19e2',
    ),
    'hoa/regex' => 
    array (
      'pretty_version' => '1.17.01.13',
      'version' => '1.17.01.13',
      'aliases' => 
      array (
      ),
      'reference' => '7e263a61b6fb45c1d03d8e5ef77668518abd5bec',
    ),
    'hoa/ruler' => 
    array (
      'pretty_version' => '2.17.05.16',
      'version' => '2.17.05.16',
      'aliases' => 
      array (
      ),
      'reference' => '696835daf8336dfd490f032da7af444050e52dfc',
    ),
    'hoa/stream' => 
    array (
      'pretty_version' => '1.17.02.21',
      'version' => '1.17.02.21',
      'aliases' => 
      array (
      ),
      'reference' => '3293cfffca2de10525df51436adf88a559151d82',
    ),
    'hoa/ustring' => 
    array (
      'pretty_version' => '4.17.01.16',
      'version' => '4.17.01.16',
      'aliases' => 
      array (
      ),
      'reference' => 'e6326e2739178799b1fe3fdd92029f9517fa17a0',
    ),
    'hoa/visitor' => 
    array (
      'pretty_version' => '2.17.01.16',
      'version' => '2.17.01.16',
      'aliases' => 
      array (
      ),
      'reference' => 'c18fe1cbac98ae449e0d56e87469103ba08f224a',
    ),
    'hoa/zformat' => 
    array (
      'pretty_version' => '1.17.01.10',
      'version' => '1.17.01.10',
      'aliases' => 
      array (
      ),
      'reference' => '522c381a2a075d4b9dbb42eb4592dd09520e4ac2',
    ),
    'owncloud/firewall' => 
    array (
      'pretty_version' => 'v2.10.3RC1',
      'version' => '2.10.3.0-RC1',
      'aliases' => 
      array (
      ),
      'reference' => 'af258dfbb566bdfd429650d2e395c5a0f4850c97',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {

foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}

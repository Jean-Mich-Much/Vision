<?php
declare(strict_types=1);
/* vision_autoclean.php */

final class VisionAutoclean
{private static bool $running=false;

public static function run(): void
{if(self::$running) return;
self::$running=true;
$wave=VisionWave::get();
if($wave['disk']!=='idle'||$wave['lock']!=='free'||$wave['charge']>=0.5)
{self::$running=false;return;}
VisionLock::acquire(function() use($wave)
{$now=VisionWave::get();
if($now['disk']!=='idle'||$now['lock']!=='free'||$now['charge']>=0.5)return;
self::clean();});
self::$running=false;}

private static function clean(): void
{$shards=Vision_UniCell::shards();
foreach($shards as $dir)
{$files=scandir($dir);
if(!is_array($files))continue;
foreach($files as $f)
{if($f==='.'||$f==='..')continue;
if(str_ends_with($f, '.tmp'))continue; // Ne pas toucher aux écritures en cours
$path=$dir.'/'.$f;
if(!is_file($path))continue;
$size=@filesize($path);
if($size===0)@unlink($path);}}}}

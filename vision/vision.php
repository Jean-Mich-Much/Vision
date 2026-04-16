<?php

/* vision.php */

require __DIR__ . '/Vision_UniCell.php';
require __DIR__ . '/Vision_API.php';
require __DIR__ . '/vision_wave.php';
require __DIR__ . '/vision_lock.php';
require __DIR__ . '/vision_worker.php';
require __DIR__ . '/vision_autoclean.php';

final class Vision
{private static bool $init = false;
private const LOG = __DIR__ . '/vision.log';

public static function init(): void
{if (self::$init) return;
self::$init = true;
VisionWave::init();
self::rotateLog();}

private static function rotateLog(): void
{$max = 32768;
$old = time() - 2592000;
if (file_exists(self::LOG))
{if (filesize(self::LOG) > $max) file_put_contents(self::LOG,'');
elseif (filemtime(self::LOG) < $old) file_put_contents(self::LOG,'');}
else file_put_contents(self::LOG,'');
chmod(self::LOG,0664);}

public static function log(string $msg): void
{self::rotateLog();
file_put_contents(self::LOG,date('c').' '.$msg."\n",FILE_APPEND|LOCK_EX);}

public static function wave(): array
{return VisionWave::get();}

public static function setWave(string $key, mixed $value): void
{VisionWave::set($key,$value);
self::log('wave '.$key.'='.$value);}

public static function updateWave(array $changes): void
{VisionWave::update($changes);
self::log('wave update');}

public static function tick(): void
{VisionWorker::run();
VisionAutoclean::run();}
}

Vision::init();

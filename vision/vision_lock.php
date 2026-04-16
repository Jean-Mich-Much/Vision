<?php

/* vision_lock.php */

final class VisionLock
{
private const FILE=__DIR__.'/vision.lock';
private static array $queue=[];
private static bool $locked=false;

public static function acquire(callable $task): void
{self::$queue[]=$task;self::process();}

private static function process(): void
{if(self::$locked) return;
if(empty(self::$queue)) return;
if(!self::tryLock()) return;
self::$locked=true;
VisionWave::set('lock','busy');
$task=array_shift(self::$queue);
try{$task();}finally{self::release();}}

private static function tryLock(): bool
{$tmp=self::FILE.'.tmp';
if(file_put_contents($tmp,'1',LOCK_EX)===false)return false;
chmod($tmp,0664);
return rename($tmp,self::FILE);}

private static function release(): void
{if(file_exists(self::FILE))unlink(self::FILE);
self::$locked=false;
VisionWave::set('lock','free');
self::process();}}

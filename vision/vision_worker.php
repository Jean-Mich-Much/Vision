<?php

/* vision_worker.php */

final class VisionWorker
{private static array $queue=[];
private static bool $running=false;

public static function add(callable $task,string $type='write'): void
{self::$queue[]=['task'=>$task,'type'=>$type];}

public static function run(): void
{if(self::$running) return;
self::$running=true;
$wave=VisionWave::get();
if(empty(self::$queue)){self::$running=false;return;}
$next=self::nextTask($wave);
if($next===null){self::$running=false;return;}
$fiber=new Fiber(function() use($next){$next();});
$fiber->start();
self::$running=false;}

private static function nextTask(array $wave): ?callable
{$prio=$wave['priorite'];
foreach(self::$queue as $i=>$item)
{if($item['type']===$prio)
{$task=$item['task'];unset(self::$queue[$i]);self::$queue=array_values(self::$queue);return $task;}}
$item=array_shift(self::$queue);
return $item['task']??null;}}

<?php
declare(strict_types=1);
/* vision_worker.php */

final class VisionWorker
{private static array $queue=[];
private static array $fibers=[]; // Suivi des tâches en cours
private static bool $running=false;

public static function add(callable $task,string $type='write'): void
{self::$queue[]=['task'=>$task,'type'=>$type];}

public static function run(): void
{if(self::$running) return;
self::$running=true;

// 1. Reprendre les Fibers suspendues (Multitâche coopératif)
self::$fibers = array_filter(self::$fibers, fn($f) => $f instanceof Fiber && !$f->isTerminated());
foreach (self::$fibers as $fiber) {
    if ($fiber->isSuspended()) {
        try { $fiber->resume(); } catch (Throwable $e) { Vision::log("Worker Resume Error: ".$e->getMessage()); }
    }
}

$wave=VisionWave::get();
if(empty(self::$queue) && empty(self::$fibers)){self::$running=false;return;}

$next=self::nextTask($wave);
if($next===null || count(self::$fibers) >= 16){self::$running=false;return;}

// Gestion améliorée des Fibers pour ne pas perdre de tâches en cas d'erreur
$fiber=new Fiber(function() use($next){
    try {
        $next();
    } catch (Throwable $e) {
        Vision::log("Worker Error: ".$e->getMessage());
    }
});

try {
    $fiber->start();
    if (!$fiber->isTerminated()) self::$fibers[] = $fiber;
} catch (Throwable $e) {
    Vision::log("Fiber Fail: ".$e->getMessage());
}
self::$running=false;}

private static function nextTask(array $wave): ?callable
{$prio=$wave['priorite'];
foreach(self::$queue as $i=>$item)
{if($item['type']===$prio)
{$task=$item['task'];array_splice(self::$queue,$i,1);return $task;}}
$item=array_shift(self::$queue);
return $item['task']??null;}}

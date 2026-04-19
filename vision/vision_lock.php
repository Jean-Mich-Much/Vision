<?php
declare(strict_types=1);
/* vision_lock.php */

final class VisionLock
{
private static array $queues=[];
private static array $active_locks=[];

public static function acquire(callable $task, string $scope = 'global'): void
{
    if (isset(self::$active_locks[$scope]) && !Fiber::getCurrent()) {
        $task();
        return;
    }
    self::$queues[$scope][] = $task;
    self::process($scope);
}

private static function process(string $scope): void
{if(isset(self::$active_locks[$scope])) return;
Vision::monitorMemory(); // Vérification RAM avant d'empiler des tâches
if(empty(self::$queues[$scope])) return;

$lockFile = __DIR__ . '/vision_' . $scope . '.lock';
$attempts = 0;
while(!self::tryLock($lockFile))
{if(class_exists('Fiber') && Fiber::getCurrent()){ Fiber::suspend(); }
else{ usleep($attempts < 20 ? 250 : 2000); $attempts++; }
if($attempts > 1000) { Vision::log("Lock Timeout on $scope"); return; } 
}

self::$active_locks[$scope] = true;
VisionWave::set('lock','busy');
$task = array_shift(self::$queues[$scope]);
try { 
    $task(); 
} catch (Throwable $e) { 
    Vision::log("Lock Task Error [$scope]: ".$e->getMessage()); 
} finally { 
    self::release($scope, $lockFile); 
} }

private static function tryLock(string $lockFile): bool
{$exists = @file_exists($lockFile);
if ($exists) {
    if (time() - @filemtime($lockFile) > 600) { @unlink($lockFile); }
    else { return false; }
}
$fp = @fopen($lockFile, 'x');
if (!$fp) return false;
@fclose($fp);
@chmod($lockFile, 0664);
return true;}

private static function release(string $scope, string $lockFile): void
{if(file_exists($lockFile)) @unlink($lockFile);
unset(self::$active_locks[$scope]);
VisionWave::set('lock','free');
if(!empty(self::$queues[$scope])) self::process($scope);}}

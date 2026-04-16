<?php

/* Vision_UniCell.php */

final class Vision_UniCell
{private const DIR=__DIR__.'/data';

private static function ensureRoot(): void
{if(!is_dir(self::DIR)){mkdir(self::DIR,0775,true);chmod(self::DIR,0775);}}

public static function id(): string
{$date=date('Ymd');
$rand=bin2hex(random_bytes(6));
return $date.'_'.$rand;}

private static function shard(string $id): string
{$d=substr($id,0,8);
return self::DIR.'/'.$d;}

public static function path(string $id): string
{return self::shard($id).'/'.$id.'.vr';}

private static function ensure(string $dir): void
{if(!is_dir($dir)){mkdir($dir,0775,true);chmod($dir,0775);}}

public static function encode(array $cell): string
{$json=json_encode($cell,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
$base=base64_encode($json);
return "VISION1\n⟪¦".$base."¦⟫\n⟪⇒¦⇐⟫\n";}

public static function decode(string $raw): ?array
{if(!str_contains($raw,'⟪⇒¦⇐⟫'))return null;
$pos=strpos($raw,'⟪¦');
if($pos===false)return null;
$end=strpos($raw,'¦⟫',$pos);
if($end===false)return null;
$base=substr($raw,$pos+5,$end-$pos-5);
$json=base64_decode($base,true);
if($json===false)return null;
$cell=json_decode($json,true);
return is_array($cell)?$cell:null;}

public static function read(string $id): ?array
{$path=self::path($id);
if(!file_exists($path))return null;
$raw=file_get_contents($path);
if($raw===false)return null;
return self::decode($raw);}

public static function write(string $id,array $cell): bool
{self::ensureRoot();
$dir=self::shard($id);
self::ensure($dir);
$path=self::path($id);
$tmp=$path.'.tmp';
$data=self::encode($cell);
if(file_put_contents($tmp,$data,LOCK_EX)===false)return false;
chmod($tmp,0664);
return rename($tmp,$path);}

public static function delete(string $id): bool
{$path=self::path($id);
return file_exists($path)?unlink($path):false;}

public static function exists(string $id): bool
{return file_exists(self::path($id));}

public static function shards(): array
{self::ensureRoot();
$dirs=scandir(self::DIR);
if(!is_array($dirs))return [];
$out=[];
foreach($dirs as $d)
{if($d==='.'||$d==='..')continue;
$dir=self::DIR.'/'.$d;
if(is_dir($dir))$out[]=$dir;}
return $out;}

public static function listCells(): array
{$out=[];
foreach(self::shards() as $dir)
{$files=scandir($dir);
if(!is_array($files))continue;
foreach($files as $f)
{if(str_ends_with($f,'.vr'))$out[]=substr($f,0,-3);}}
return $out;}}

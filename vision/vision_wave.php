<?php

/* vision_wave.php */

final class VisionWave
{
private const FILE=__DIR__.'/wave.json';
private static array $wave=[
'charge'=>0.0,
'priorite'=>'idle',
'pression'=>'low',
'lock'=>'free',
'ram'=>'stable',
'disk'=>'idle',
'op'=>'none'
];

public static function init(): void
{
if(!file_exists(self::FILE))
{self::sync();return;}
$json=file_get_contents(self::FILE);
if($json===false)return;
$data=json_decode($json,true);
if(is_array($data))self::$wave=array_merge(self::$wave,$data);
}

public static function get(): array
{
return self::$wave;
}

public static function set(string $key,mixed $value): void
{
if(array_key_exists($key,self::$wave))
{self::$wave[$key]=$value;self::sync();}
}

public static function update(array $changes): void
{
foreach($changes as $k=>$v)
if(array_key_exists($k,self::$wave))self::$wave[$k]=$v;
self::sync();
}

public static function sync(): void
{
$tmp=self::FILE.'.tmp';
$json=json_encode(self::$wave,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
if($json===false)return;
if(file_put_contents($tmp,$json,LOCK_EX)===false)return;
chmod($tmp,0664);
rename($tmp,self::FILE);
}
}

VisionWave::init();

<?php

/* Vision_API.php */

final class Vision_API
{
private static function norm(array $rels): array
{$o=[];
foreach($rels as $r)
{if(isset($r['id'],$r['niveau']))$o[]=['id'=>(string)$r['id'],'niveau'=>(int)$r['niveau']];}
return $o;}

public static function create(array $data,array $relations=[]): string
{$id=Vision_UniCell::id();
$now=time();
$cell=['id'=>$id,'data'=>$data,'relations'=>self::norm($relations),'createdAt'=>$now,'updatedAt'=>$now];
VisionLock::acquire(function() use($id,$cell)
{Vision_UniCell::write($id,$cell);
Vision::log('create '.$id);});
return $id;}

public static function read(string $id): ?array
{return Vision_UniCell::read($id);}

public static function update(string $id,array $data,array $relations=[]): bool
{$cell=Vision_UniCell::read($id);
if(!$cell) return false;
$cell['data']=$data;
if($relations)$cell['relations']=self::norm($relations);
$cell['updatedAt']=time();
$ok=false;
VisionLock::acquire(function() use($id,$cell,&$ok)
{$ok=Vision_UniCell::write($id,$cell);
Vision::log('update '.$id);});
return $ok;}

public static function delete(string $id): bool
{$ok=false;
VisionLock::acquire(function() use($id,&$ok)
{$ok=Vision_UniCell::delete($id);
Vision::log('delete '.$id);});
return $ok;}

public static function exists(string $id): bool
{return Vision_UniCell::exists($id);}

public static function count(): int
{return count(Vision_UniCell::listCells());}

public static function stats(): array
{$cells=Vision_UniCell::listCells();
$total=0;
foreach($cells as $id)
{$path=Vision_UniCell::path($id);
if(!file_exists($path)) continue;
$size=filesize($path);
if($size!==false)$total+=$size;}
return ['count'=>count($cells),'size'=>$total,'shards'=>count(Vision_UniCell::shards())];}

public static function relations(string $id): array
{$c=Vision_UniCell::read($id);
return $c['relations']??[];}

public static function link(string $id,string $target,int $niveau): bool
{$c=Vision_UniCell::read($id);
if(!$c) return false;
$rels=$c['relations']??[];
$rels[]=['id'=>$target,'niveau'=>$niveau];
$c['relations']=self::norm($rels);
$c['updatedAt']=time();
$ok=false;
VisionLock::acquire(function() use($id,$c,$target,&$ok)
{$ok=Vision_UniCell::write($id,$c);
Vision::log('link '.$id.' '.$target);});
return $ok;}

public static function unlink(string $id,string $target): bool
{$c=Vision_UniCell::read($id);
if(!$c) return false;
$rels=$c['relations']??[];
$out=[];
foreach($rels as $r)
{if(($r['id']??null)!==$target)$out[]=$r;}
$c['relations']=$out;
$c['updatedAt']=time();
$ok=false;
VisionLock::acquire(function() use($id,$c,$target,&$ok)
{$ok=Vision_UniCell::write($id,$c);
Vision::log('unlink '.$id.' '.$target);});
return $ok;}

public static function graph(string $id): array
{$seen=[];
$out=[];
self::walk($id,$seen,$out,0);
return $out;}

private static function walk(string $id,array &$seen,array &$out,int $d): void
{if(isset($seen[$id])) return;
if($d>16) return;
$c=Vision_UniCell::read($id);
if(!$c) return;
$seen[$id]=true;
$out[$id]=$c;
$rels=$c['relations']??[];
foreach($rels as $r)
{if(!isset($r['id'])) continue;
self::walk($r['id'],$seen,$out,$d+1);}}

public static function find(callable $f): array
{$out=[];
foreach(Vision_UniCell::listCells() as $id)
{$c=Vision_UniCell::read($id);
if($c&&$f($c))$out[]=$c;}
return $out;}

public static function clean(): void
{VisionAutoclean::run();}}

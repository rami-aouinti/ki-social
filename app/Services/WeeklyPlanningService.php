<?php
namespace App\Services;
use InvalidArgumentException;
final class WeeklyPlanningService {
 public function generate(array $input):array {
  foreach(['week','people','inspirations'] as $key)if(!isset($input[$key]))throw new InvalidArgumentException("Missing input: {$key}");
  if(count($input['inspirations'])<3)throw new InvalidArgumentException('At least three inspirations are required.');
  $ideas=[
   ['id'=>'IDEA-001','topic'=>'Welche Brillenform passt zu deinem Gesicht?','priority'=>'priority','channels'=>['instagram','linkedin'],'format'=>'Short video + professional post','owner'=>'A','date'=>'2026-11-24','effort'=>55,'status'=>'draft','reason'=>'Beratungsnah, visuell und kanalübergreifend sinnvoll.'],
   ['id'=>'IDEA-002','topic'=>'Mythos oder Fakt: Bildschirm und Augen','priority'=>'priority','channels'=>['facebook','instagram'],'format'=>'Carousel','owner'=>'B','date'=>'2026-11-25','effort'=>65,'status'=>'draft','reason'=>'Beantwortet eine Alltagsfrage; fachliche Aussagen vor Freigabe prüfen.'],
   ['id'=>'IDEA-003','topic'=>'Ein Blick hinter die Kulissen der Anpassung','priority'=>'priority','channels'=>['linkedin','facebook'],'format'=>'Photo story','owner'=>'B','date'=>'2026-11-27','effort'=>70,'status'=>'draft','reason'=>'Macht persönliche Beratung und Service sichtbar.'],
   ['id'=>'IDEA-004','topic'=>'Winterlicht und Sehkomfort','priority'=>'reserve','channels'=>['instagram','facebook'],'format'=>'Story sequence','owner'=>'A','date'=>'2026-11-28','effort'=>30,'status'=>'draft','reason'=>'Saisonaler Anlass; bei knapper Kapazität zurückstellen.'],
   ['id'=>'IDEA-005','topic'=>'Brillenpflege im Alltag','priority'=>'reserve','channels'=>['tiktok','instagram'],'format'=>'Short video','owner'=>'B','date'=>'2026-11-29','effort'=>45,'status'=>'draft','reason'=>'Evergreen-Thema und später nachproduzierbar.']
  ];
  $capacity=[];$warnings=[];
  foreach($input['people'] as $person=>$available){$planned=array_sum(array_column(array_filter($ideas,fn($i)=>$i['owner']===$person&&$i['priority']==='priority'),'effort'));$remaining=(int)$available-$planned;$capacity[$person]=['available_minutes'=>(int)$available,'planned_minutes'=>$planned,'remaining_minutes'=>$remaining,'warning'=>$remaining<0];if($remaining<0)$warnings[]="Person {$person} exceeds capacity by ".abs($remaining)." minutes. Move/reduce a priority item; never increase availability silently.";}
  return ['week'=>$input['week'],'business'=>$input['business']??'Optiker Demo','ideas'=>$ideas,'inspirations'=>array_map(fn($i)=>$i+['trend_status'=>'unverified','metrics'=>null],$input['inspirations']),'selection_rationale'=>'Three priorities balance advice, education and trust. Two reserves provide flexibility. Sources are inspiration only; missing metrics remain unknown and no trend is asserted.','capacity'=>$capacity,'warnings'=>$warnings,'assumptions'=>['Effort includes production, variants, coordination and approval.','Reserve ideas are not included in committed workload.','All dates and source identifiers are synthetic.']];
 }
 public function report(array $plan):string{$out=['KI SOCIAL — WEEKLY OVERVIEW','Week: '.$plan['week'],'Business: '.$plan['business'],'','IDEAS'];foreach($plan['ideas'] as $i)$out[]=sprintf('%s | %s | %s | %s | %s | %d min',$i['id'],strtoupper($i['priority']),$i['topic'],implode(',',$i['channels']),$i['owner'],$i['effort']);$out[]='';$out[]='CAPACITY';foreach($plan['capacity'] as $p=>$c)$out[]=sprintf('%s: %d/%d min; remaining %d%s',$p,$c['planned_minutes'],$c['available_minutes'],$c['remaining_minutes'],$c['warning']?' — WARNING':'');$out[]='';$out[]='Inspirations are unverified; missing metrics remain unknown.';return implode(PHP_EOL,$out).PHP_EOL;}
}
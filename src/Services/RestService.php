<?php
namespace App\Services;
use DateTimeImmutable;
final class RestService {
    public static function status(?string $last,?DateTimeImmutable $now=null): array {
        $now=$now??new DateTimeImmutable();
        if ($last===null) return ['ultimo'=>null,'disponivel_em'=>null,'em_descanso'=>false,'status'=>'Pode treinar','treinado_semana'=>false];
        $date=new DateTimeImmutable($last);$available=$date->modify('+48 hours');
        $start=$now->modify('monday this week')->setTime(0,0);
        return ['ultimo'=>$date->format(DATE_ATOM),'disponivel_em'=>$available->format(DATE_ATOM),
            'em_descanso'=>$now<$available,'status'=>$now<$available?'Em descanso':'Pode treinar novamente',
            'treinado_semana'=>$date>=$start && $date<=$now];
    }
    public static function summary(array $rows,?DateTimeImmutable $now=null): array {
        $now=$now??new DateTimeImmutable();$out=[];
        foreach (require APP_ROOT.'/config/groups.php' as $key=>$name) {
            $row=null;foreach($rows as $r) if($r['grupo_muscular']===$key) {$row=$r;break;}
            $out[]=['grupo_muscular'=>$key,'nome'=>$name,'quantidade'=>(int)($row['quantidade']??0)]+self::status($row['ultimo']??null,$now);
        }
        return ['grupos'=>$out,'agora'=>$now->format(DATE_ATOM)];
    }
}

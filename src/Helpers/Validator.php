<?php
namespace App\Helpers;
final class Validator {
    public static function text(array $data,string $key,int $min,int $max): string {
        $v=$data[$key] ?? '';
        if (!is_string($v)) throw new HttpError(422,'Confira os campos informados.',[$key=>'Informe um texto.']);
        $v=trim($v);
        $len=preg_match_all('/./us',$v);
        if ($len===false || $len<$min || $len>$max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$v))
            throw new HttpError(422,'Confira os campos informados.',[$key=>"Use entre $min e $max caracteres, sem caracteres de controle."]);
        return $v;
    }
    public static function integer($v,string $key,int $min=1,int $max=2147483647): int {
        if ((!is_int($v) && !is_string($v)) || filter_var($v,FILTER_VALIDATE_INT)===false || (int)$v<$min || (int)$v>$max)
            throw new HttpError(422,'Confira os campos informados.',[$key=>"Informe um inteiro entre $min e $max."]);
        return (int)$v;
    }
    public static function email(array $data): string {
        $email=strtolower(self::text($data,'email',3,190));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new HttpError(422,'Confira os campos informados.',['email'=>'Informe um e-mail válido.']);
        return $email;
    }
    public static function password(array $data): string {
        $v=$data['senha'] ?? null;
        if (!is_string($v) || strlen($v)<8 || strlen($v)>72 || strpos($v,"\0")!==false)
            throw new HttpError(422,'Confira os campos informados.',['senha'=>'Use de 8 a 72 bytes (acentos podem ocupar mais de um byte).']);
        return $v;
    }
    public static function choice(array $data,string $key,array $allowed): string {
        $v=self::text($data,$key,1,30);
        if (!in_array($v,$allowed,true)) throw new HttpError(422,'Confira os campos informados.',[$key=>'Selecione uma opção válida.']);
        return $v;
    }
    public static function group(array $data): string {
        return self::choice($data,'grupo_muscular',array_keys(require APP_ROOT.'/config/groups.php'));
    }
    public static function optionalInteger($value,string $key,int $max): ?int {
        return $value===null || $value==='' ? null : self::integer($value,$key,1,$max);
    }
    public static function optionalLoad($v): ?float {
        if ($v===null || $v==='') return null;
        if ((!is_string($v) && !is_int($v) && !is_float($v)) || !preg_match('/^\d{1,4}(\.\d{1,2})?$/',(string)$v) || (float)$v>1000)
            throw new HttpError(422,'Confira os campos informados.',['carga'=>'Informe de 0 a 1000 kg, com até duas casas decimais.']);
        return (float)$v;
    }
    public static function workout(array $data): array {
        if (($data['confirmado']??null)!==true) throw new HttpError(422,'Confirme que realizou este treino.');
        if (array_key_exists('data_treino',$data)) throw new HttpError(422,'A data e hora são registradas automaticamente pelo servidor.');
        $group=self::group($data);
        $items=$data['exercicios']??null;
        if (!is_array($items) || count($items)<1 || count($items)>20 || array_keys($items)!==range(0,count($items)-1))
            throw new HttpError(422,'Marque ao menos um exercício ou descreva o que fez (máximo 20 itens).');
        $out=[];$seen=[];$freeSeen=false;
        foreach ($items as $raw) {
            if (is_object($raw)) $raw=(array)$raw;
            if (!is_array($raw)) throw new HttpError(422,'Exercício inválido.');
            $id=$raw['exercicio_id']??null;
            $id=$id===null || $id==='' ? null : self::integer($id,'exercicio_id');
            $free=self::text($raw,'descricao_livre',0,500);
            if (($id===null && $free==='') || ($id!==null && $free!=='')) throw new HttpError(422,'Cada item deve ter um exercício do catálogo OU uma descrição livre.');
            if ($id===null) {if ($freeSeen) throw new HttpError(422,'Use um único campo de texto livre para descrever os outros exercícios.');$freeSeen=true;}
            if ($id!==null && isset($seen[$id])) throw new HttpError(422,'Não repita o mesmo exercício no treino.');
            if ($id!==null) $seen[$id]=true;
            $out[]=['exercicio_id'=>$id,'descricao_livre'=>$free,'series'=>self::optionalInteger($raw['series']??null,'series',30),
                'repeticoes'=>self::optionalInteger($raw['repeticoes']??null,'repeticoes',1000),'carga'=>self::optionalLoad($raw['carga']??null)];
        }
        return ['grupo_muscular'=>$group,'observacao'=>self::text($data,'observacao',0,1000),'exercicios'=>$out];
    }
    public static function video(array $data): string {
        $v=self::text($data,'video_url',0,1000);
        if ($v==='') return '';
        $parts=parse_url($v);
        if (!filter_var($v,FILTER_VALIDATE_URL) || !$parts || ($parts['scheme']??'')!=='https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x20]/',$v))
            throw new HttpError(422,'Link de vídeo inválido.',['video_url'=>'Use uma URL completa https://, sem usuário/senha, ou deixe vazio.']);
        return $v;
    }
    public static function exercise(array $data): array {
        return ['nome'=>self::text($data,'nome',2,100),'grupo_muscular'=>self::group($data),
            'descricao'=>self::text($data,'descricao',0,1000),'video_url'=>self::video($data)];
    }
    public static function profile(array $data): array {
        return ['nivel'=>self::choice($data,'nivel',['iniciante','intermediario','avancado']),
            'objetivo'=>self::choice($data,'objetivo',['hipertrofia','forca','condicionamento','geral']),
            'tempo_disponivel'=>self::choice($data,'tempo_disponivel',['30','60','90'])];
    }
}

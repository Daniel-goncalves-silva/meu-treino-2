<?php
namespace App\Services;
use App\Repositories\ExerciseRepository;
use App\Helpers\Validator;

// Esta classe só recebe o catálogo. Não possui acesso a gravação de treinos.
final class SuggestionService {
    private ExerciseRepository $exercises;
    public function __construct(ExerciseRepository $exercises) {$this->exercises=$exercises;}

    public function generate(array $data): array {
        $group=Validator::group($data);
        $time=Validator::choice($data,'tempo',['30','60','90']);
        $level=isset($data['nivel']) ? Validator::choice($data,'nivel',['iniciante','intermediario','avancado']) : 'iniciante';
        $goal=isset($data['objetivo']) ? Validator::choice($data,'objetivo',['hipertrofia','forca','condicionamento','geral']) : 'geral';

        $count=['30'=>3,'60'=>5,'90'=>6][$time];
        $items=$this->exercises->byGroup($group);
        shuffle($items);

        $guidance=$this->guidance($level,$goal);
        return [
            'grupo_muscular'=>$group,
            'tempo'=>$time,
            'nivel'=>$level,
            'objetivo'=>$goal,
            'orientacao'=>$guidance,
            'quantidade_alvo'=>$count,
            'quantidade_disponivel'=>count($items),
            'exercicios'=>array_slice($items,0,$count),
            'aviso'=>'Referência geral de organização do treino. Ajuste conforme sua condição e orientação profissional; só será registrada quando você confirmar que realizou.'
        ];
    }

    private function guidance(string $level,string $goal): array {
        $series=[
            'iniciante'=>'2–3 séries',
            'intermediario'=>'3 séries',
            'avancado'=>'3–4 séries',
        ][$level];

        $goalData=[
            'hipertrofia'=>['repeticoes'=>'8–12 repetições','descanso'=>'60–90 s','label'=>'Hipertrofia'],
            'forca'=>['repeticoes'=>'4–6 repetições','descanso'=>'2–3 min','label'=>'Força'],
            'condicionamento'=>['repeticoes'=>'12–20 repetições','descanso'=>'30–60 s','label'=>'Condicionamento'],
            'geral'=>['repeticoes'=>'8–12 repetições','descanso'=>'60–90 s','label'=>'Geral'],
        ][$goal];

        $levelLabel=[
            'iniciante'=>'Iniciante',
            'intermediario'=>'Intermediário',
            'avancado'=>'Avançado',
        ][$level];

        return [
            'nivel_label'=>$levelLabel,
            'objetivo_label'=>$goalData['label'],
            'series'=>$series,
            'repeticoes'=>$goalData['repeticoes'],
            'descanso'=>$goalData['descanso'],
        ];
    }
}

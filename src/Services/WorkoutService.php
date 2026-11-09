<?php
namespace App\Services;
use App\Repositories\{WorkoutRepository,ExerciseRepository};
use App\Helpers\{Validator,HttpError,Logger};
final class WorkoutService {
    private WorkoutRepository $workouts;private ExerciseRepository $exercises;
    private $clock;
    public function __construct(WorkoutRepository $w,ExerciseRepository $e,?callable $clock=null) {
        $this->workouts=$w;$this->exercises=$e;$this->clock=$clock??fn()=>new \DateTimeImmutable();
    }
    public function owned(int $id,int $user): array {
        $record=$this->workouts->findOwned($id,$user);
        if (!$record) throw new HttpError(404,'Treino não encontrado.');return $record;
    }
    public function save(int $user,array $data,?int $id=null): int {
        $old=$id!==null ? $this->owned($id,$user) : null;
        $valid=Validator::workout($data);
        foreach ($valid['exercicios'] as $item) {
            if ($item['exercicio_id']===null) continue;
            $e=$this->exercises->find($item['exercicio_id']);
            if (!$e || $e['grupo_muscular']!==$valid['grupo_muscular']) throw new HttpError(422,'Escolha exercícios pertencentes ao grupo selecionado.');
        }
        // Na edição, preserva o horário confirmado original: editar não reinicia o descanso.
        $valid['data_treino']=$old['data_treino']??($this->clock)()->format('Y-m-d H:i:s');
        if ($id===null) {
            $id=$this->workouts->create($user,$valid);
            Logger::write('treino_criado',['usuario_id'=>$user,'treino_id'=>$id,'itens'=>count($valid['exercicios'])]);
        } else {$this->workouts->update($id,$user,$valid);Logger::write('treino_editado',['usuario_id'=>$user,'treino_id'=>$id]);}
        return $id;
    }
    public function delete(int $id,int $user): void {
        $this->owned($id,$user);$this->workouts->delete($id,$user);
        Logger::write('treino_excluido',['usuario_id'=>$user,'treino_id'=>$id]);
    }
}

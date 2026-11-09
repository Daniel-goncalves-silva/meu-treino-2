<?php
namespace App\Services;
use App\Repositories\ExerciseRepository;
use App\Helpers\{Validator,HttpError};
final class ExerciseService {
    private ExerciseRepository $repo;
    public function __construct(ExerciseRepository $repo) {$this->repo=$repo;}
    public function get(int $id): array {$e=$this->repo->find($id);if (!$e) throw new HttpError(404,'Exercício não encontrado.');return $e;}
    public function save(array $data,?int $id=null): int {
        $valid=Validator::exercise($data);
        if ($id!==null) {
            $old=$this->get($id);
            if ($old['grupo_muscular']!==$valid['grupo_muscular'] && $this->repo->used($id)) throw new HttpError(409,'Exercício possui treinos: mantenha o grupo muscular para preservar o histórico.');
        }
        try {if ($id===null) return $this->repo->create($valid);$this->repo->update($id,$valid);return $id;}
        catch (\PDOException $e) {if (($e->errorInfo[1]??null)===1062) throw new HttpError(409,'Já existe um exercício com este nome.');throw $e;}
    }
    public function delete(int $id): void {
        $this->get($id);
        try {$this->repo->delete($id);} catch (\PDOException $e) {
            if (($e->errorInfo[1]??null)===1451) throw new HttpError(409,'Exercício usado em treinos: exclusão não permitida.');throw $e;
        }
    }
}

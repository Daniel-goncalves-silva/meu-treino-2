<?php
namespace App\Repositories;
use PDO;
use App\Helpers\HttpError;
class WorkoutRepository {
    protected PDO $db;
    public function __construct(PDO $db) {$this->db=$db;}
    // A leitura dos itens também restringe usuario_id, não apenas o treino_id.
    private function items(int $id,int $user): array {
        $q=$this->db->prepare('SELECT te.id,te.exercicio_id,te.descricao_livre,te.series,te.repeticoes,te.carga,e.nome,e.descricao,e.video_url FROM treino_exercicios te JOIN treinos t ON t.id=te.treino_id LEFT JOIN exercicios e ON e.id=te.exercicio_id WHERE te.treino_id=:id AND t.usuario_id=:uid ORDER BY te.id');
        $q->execute(['id'=>$id,'uid'=>$user]);return $q->fetchAll();
    }
    public function findOwned(int $id,int $user): ?array {
        $q=$this->db->prepare('SELECT id,usuario_id,grupo_muscular,data_treino,observacao,criado_em FROM treinos WHERE id=:id AND usuario_id=:uid');
        $q->execute(['id'=>$id,'uid'=>$user]);$r=$q->fetch();
        if(!$r)return null;$r['exercicios']=$this->items($id,$user);return $r;
    }
    public function listing(int $user,int $page): array {
        $q=$this->db->prepare('SELECT id,usuario_id,grupo_muscular,data_treino,observacao,criado_em FROM treinos WHERE usuario_id=:uid ORDER BY data_treino DESC,id DESC LIMIT 10 OFFSET :offset');
        $q->bindValue(':uid',$user,PDO::PARAM_INT);$q->bindValue(':offset',($page-1)*10,PDO::PARAM_INT);$q->execute();
        $rows=$q->fetchAll();foreach($rows as &$r)$r['exercicios']=$this->items((int)$r['id'],$user);unset($r);
        $count=$this->db->prepare('SELECT COUNT(*) FROM treinos WHERE usuario_id=:uid');$count->execute(['uid'=>$user]);
        return ['items'=>$rows,'total'=>(int)$count->fetchColumn(),'page'=>$page,'per_page'=>10];
    }
    private function insertItems(int $id,array $items,string $group): void {
        // Bloqueio de leitura dos exercícios impede mudança concorrente de grupo durante a gravação.
        $lock=$this->db->prepare('SELECT grupo_muscular FROM exercicios WHERE id=:id LOCK IN SHARE MODE');
        $q=$this->db->prepare('INSERT INTO treino_exercicios(treino_id,exercicio_id,descricao_livre,series,repeticoes,carga) VALUES(:tid,:exercicio_id,:descricao_livre,:series,:repeticoes,:carga)');
        foreach($items as $item) {
            if($item['exercicio_id']!==null){$lock->execute(['id'=>$item['exercicio_id']]);if($lock->fetchColumn()!==$group)throw new HttpError(422,'O catálogo mudou. Atualize a página e tente novamente.');}
            $q->execute($item+['tid'=>$id]);
        }
    }
    public function create(int $user,array $d): int {
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('INSERT INTO treinos(usuario_id,grupo_muscular,data_treino,observacao) VALUES(:uid,:grupo,:data,:observacao)');
            $q->execute(['uid'=>$user,'grupo'=>$d['grupo_muscular'],'data'=>$d['data_treino'],'observacao'=>$d['observacao']]);
            $id=(int)$this->db->lastInsertId();$this->insertItems($id,$d['exercicios'],$d['grupo_muscular']);$this->db->commit();return $id;
        } catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function update(int $id,int $user,array $d): void {
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT id FROM treinos WHERE id=:id AND usuario_id=:uid FOR UPDATE');$q->execute(['id'=>$id,'uid'=>$user]);
            if(!$q->fetchColumn())throw new HttpError(404,'Treino não encontrado.');
            $q=$this->db->prepare('UPDATE treinos SET grupo_muscular=:grupo,observacao=:observacao WHERE id=:id AND usuario_id=:uid');
            $q->execute(['grupo'=>$d['grupo_muscular'],'observacao'=>$d['observacao'],'id'=>$id,'uid'=>$user]);
            $q=$this->db->prepare('DELETE te FROM treino_exercicios te JOIN treinos t ON t.id=te.treino_id WHERE t.id=:id AND t.usuario_id=:uid');$q->execute(['id'=>$id,'uid'=>$user]);
            $this->insertItems($id,$d['exercicios'],$d['grupo_muscular']);$this->db->commit();
        } catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function delete(int $id,int $user): void {
        $q=$this->db->prepare('DELETE FROM treinos WHERE id=:id AND usuario_id=:uid');$q->execute(['id'=>$id,'uid'=>$user]);
        // FK treino_exercicios ON DELETE CASCADE exclui somente os itens deste treino.
    }
    public function summary(int $user): array {
        $q=$this->db->prepare('SELECT grupo_muscular,MAX(data_treino) AS ultimo,COUNT(*) AS quantidade FROM treinos WHERE usuario_id=:uid GROUP BY grupo_muscular');
        $q->execute(['uid'=>$user]);return $q->fetchAll();
    }
    public function totals(): array {
        return $this->db->query('SELECT (SELECT COUNT(*) FROM usuarios) AS usuarios,(SELECT COUNT(*) FROM exercicios) AS exercicios,(SELECT COUNT(*) FROM treinos) AS registros')->fetch();
    }
}

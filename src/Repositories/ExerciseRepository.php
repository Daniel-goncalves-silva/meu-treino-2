<?php
namespace App\Repositories;
use PDO;
class ExerciseRepository {
    protected PDO $db;
    public function __construct(PDO $db) {$this->db=$db;}
    public function all(): array {return $this->db->query('SELECT id,nome,grupo_muscular,descricao,video_url FROM exercicios ORDER BY grupo_muscular,nome')->fetchAll();}
    public function find(int $id): ?array {
        $q=$this->db->prepare('SELECT id,nome,grupo_muscular,descricao,video_url FROM exercicios WHERE id=:id');$q->execute(['id'=>$id]);return $q->fetch() ?: null;
    }
    public function create(array $d): int {
        $q=$this->db->prepare('INSERT INTO exercicios(nome,grupo_muscular,descricao,video_url) VALUES(:nome,:grupo_muscular,:descricao,:video_url)');$q->execute($d);return (int)$this->db->lastInsertId();
    }
    public function update(int $id,array $d): void {
        $q=$this->db->prepare('UPDATE exercicios SET nome=:nome,grupo_muscular=:grupo_muscular,descricao=:descricao,video_url=:video_url WHERE id=:id');$q->execute($d+['id'=>$id]);
    }
    public function used(int $id): bool {
        $q=$this->db->prepare('SELECT 1 FROM treino_exercicios WHERE exercicio_id=:id LIMIT 1');$q->execute(['id'=>$id]);return (bool)$q->fetchColumn();
    }
    public function delete(int $id): void {$q=$this->db->prepare('DELETE FROM exercicios WHERE id=:id');$q->execute(['id'=>$id]);}
    public function byGroup(string $group): array {
        $q=$this->db->prepare('SELECT id,nome,grupo_muscular,descricao,video_url FROM exercicios WHERE grupo_muscular=:grupo ORDER BY nome');
        $q->execute(['grupo'=>$group]);return $q->fetchAll();
    }
}

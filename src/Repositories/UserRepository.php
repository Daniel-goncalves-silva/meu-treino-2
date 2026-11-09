<?php
namespace App\Repositories;
use PDO;
class UserRepository {
    protected PDO $db;
    public function __construct(PDO $db) {$this->db=$db;}
    public function byEmail(string $email): ?array {
        $q=$this->db->prepare('SELECT id,nome,email,senha_hash,perfil FROM usuarios WHERE email=:email');
        $q->execute(['email'=>$email]); return $q->fetch() ?: null;
    }
    public function find(int $id): ?array {
        $q=$this->db->prepare('SELECT id,nome,email,perfil FROM usuarios WHERE id=:id');
        $q->execute(['id'=>$id]); return $q->fetch() ?: null;
    }
    public function create(string $name,string $email,string $hash): int {
        $q=$this->db->prepare("INSERT INTO usuarios(nome,email,senha_hash,perfil) VALUES(:nome,:email,:hash,'usuario')");
        $q->execute(['nome'=>$name,'email'=>$email,'hash'=>$hash]); return (int)$this->db->lastInsertId();
    }
    public function listing(int $page): array {
        $q=$this->db->prepare('SELECT id,nome,email,perfil,criado_em FROM usuarios ORDER BY id DESC LIMIT 20 OFFSET :offset');
        $q->bindValue(':offset',($page-1)*20,PDO::PARAM_INT); $q->execute();
        return ['items'=>$q->fetchAll(),'total'=>(int)$this->db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),'page'=>$page,'per_page'=>20];
    }
    public function promote(string $email): bool {
        $q=$this->db->prepare("UPDATE usuarios SET perfil='admin' WHERE email=:email");
        $q->execute(['email'=>$email]); return $q->rowCount()>0;
    }
    public function profile(int $id): array {
        $q=$this->db->prepare('SELECT nivel,objetivo,tempo_disponivel FROM usuarios WHERE id=:id');$q->execute(['id'=>$id]);
        return $q->fetch();
    }
    public function saveProfile(int $id,array $data): void {
        $q=$this->db->prepare('UPDATE usuarios SET nivel=:nivel,objetivo=:objetivo,tempo_disponivel=:tempo_disponivel WHERE id=:id');
        $q->execute($data+['id'=>$id]);
    }
}

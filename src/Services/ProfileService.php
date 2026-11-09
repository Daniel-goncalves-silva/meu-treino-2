<?php
namespace App\Services;
use App\Repositories\UserRepository;
use App\Helpers\Validator;
final class ProfileService {
    private UserRepository $users;
    public function __construct(UserRepository $users) {$this->users=$users;}
    public function save(int $id,array $data): array {
        $valid=Validator::profile($data);$this->users->saveProfile($id,$valid);return $valid;
    }
}

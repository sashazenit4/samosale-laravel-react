<?php

namespace App\Repositories;

use App\Models\LoyaltyProgramKey;

interface LoyaltyProgramKeyRepositoryInterface
{
    public function findByUserId(int $userId): ?LoyaltyProgramKey;
    public function create(array $data): LoyaltyProgramKey;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function findByKey(string $key): ?LoyaltyProgramKey;
    public function createOrUpdateByUserId(int $userId, array $data): LoyaltyProgramKey;
}

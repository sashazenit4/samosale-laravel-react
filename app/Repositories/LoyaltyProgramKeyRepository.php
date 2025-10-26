<?php

namespace App\Repositories;

use App\Models\LoyaltyProgramKey;

class LoyaltyProgramKeyRepository implements LoyaltyProgramKeyRepositoryInterface
{
    public function findByUserId(int $userId): ?LoyaltyProgramKey
    {
        return LoyaltyProgramKey::where('user_id', $userId)->first();
    }

    public function create(array $data): LoyaltyProgramKey
    {
        return LoyaltyProgramKey::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $key = LoyaltyProgramKey::find($id);

        if (!$key) {
            return false;
        }

        return $key->update($data);
    }

    public function delete(int $id): bool
    {
        $key = LoyaltyProgramKey::find($id);

        if (!$key) {
            return false;
        }

        return $key->delete();
    }

    public function findByKey(string $key): ?LoyaltyProgramKey
    {
        return LoyaltyProgramKey::where('samosale_key', $key)->first();
    }

    public function updateByUserId(int $userId, array $data): bool
    {
        /** @var LoyaltyProgramKey|null $key */
        $key = LoyaltyProgramKey::where('user_id', $userId)->first();

        if (!$key) {
            return false;
        }

        return $key->update($data);
    }

    public function createOrUpdateByUserId(int $userId, array $data): LoyaltyProgramKey
    {
        $existingKey = $this->findByUserId($userId);

        if ($existingKey) {

            $this->updateByUserId($userId, $data);
            return $this->findByUserId($userId); // Возвращаем обновленную запись
        } else {

            $data['user_id'] = $userId; // Убедимся, что user_id установлен
            return $this->create($data);
        }
    }
}

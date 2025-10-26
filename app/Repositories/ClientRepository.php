<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientRepository
{
    /**
     * Find client by user_id
     */
    public function findById(int $userId): ?Client
    {
        return Client::find($userId);
    }

    /**
     * Find client by Telegram ID
     */
    public function findByTelegramId(int $telegramId): ?Client
    {
        return Client::byTelegramId($telegramId)->first();
    }

    /**
     * Find client by phone number
     */
    public function findByPhoneNumber(string $phoneNumber): ?Client
    {
        return Client::byPhoneNumber($phoneNumber)->first();
    }

    /**
     * Find client by referral code
     */
    public function findByReferralCode(string $referralCode): ?Client
    {
        return Client::byReferralCode($referralCode)->first();
    }

    /**
     * Create new client
     */
    public function create(array $data): Client
    {
        // Генерируем реферальный код если не предоставлен
        if (!isset($data['referral_code'])) {
            $data['referral_code'] = Client::generateReferralCode();
        }

        return Client::create($data);
    }

    /**
     * Update client
     */
    public function update(int $userId, array $data): bool
    {
        $client = $this->findById($userId);

        if (!$client) {
            return false;
        }

        return $client->update($data);
    }

    /**
     * Delete client
     */
    public function delete(int $userId): bool
    {
        $client = $this->findById($userId);

        if (!$client) {
            return false;
        }

        return $client->delete();
    }

    /**
     * Get all clients with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Client::with('referrer')
            ->orderBy('registration_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get clients by referrer
     */
    public function getReferrals(int $referrerId): Collection
    {
        return Client::where('referred_by', $referrerId)
            ->with('referrer')
            ->orderBy('registration_date', 'desc')
            ->get();
    }

    /**
     * Check if Telegram ID exists
     */
    public function telegramIdExists(int $telegramId): bool
    {
        return Client::where('telegram_id', $telegramId)->exists();
    }

    /**
     * Check if phone number exists
     */
    public function phoneNumberExists(string $phoneNumber): bool
    {
        return Client::where('phone_number', $phoneNumber)->exists();
    }

    /**
     * Get client statistics
     */
    public function getStatistics(int $userId): array
    {
        $client = $this->findById($userId);

        if (!$client) {
            return [];
        }

        return [
            'referrals_count' => $client->referrals()->count(),
            'registration_date' => $client->registration_date,
            'has_referrer' => !is_null($client->referred_by),
        ];
    }
}

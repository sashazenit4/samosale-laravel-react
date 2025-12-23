<?php

namespace App\Repositories;

use App\Models\BonusOperation;
use App\Models\BonusSystemConfig;
use App\Models\Client;
use App\Models\CustomClientField;
use App\Models\ReferralInvite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\CustomFieldValidationService;
use App\Models\CustomFieldTemplate;
use Illuminate\Support\Facades\DB;

class ClientRepository
{
    protected CustomFieldValidationService $validationService;

    public function __construct(CustomFieldValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Find client by user_id
     */
    public function findById(int $userId): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->find($userId);
    }

    /**
     * Find client by Telegram ID
     */
    public function findByTelegramId(int $telegramId): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->byTelegramId($telegramId)->first();
    }

    /**
     * Find client by phone number
     */
    public function findByPhoneNumber(string $phoneNumber): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->byPhoneNumber($phoneNumber)->first();
    }

    /**
     * Find client by referral code
     */
    public function findByReferralCode(string $referralCode): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->byReferralCode($referralCode)->first();
    }

    /**
     * Create new client with validated custom fields
     */
    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            // Генерируем реферальный код если не предоставлен
            if (!isset($data['referral_code'])) {
                $data['referral_code'] = Client::generateReferralCode();
            }

            if (!isset($data['referred_by']) && isset($data['telegram_id'])) {
                $loyaltyInfo = [
                    'has_welcome_bonus' => false,
                    'is_loyalty_member' => false,
                ];
                $data['referred_by'] = $this->getReferredByFromInvites($data['telegram_id'], $loyaltyInfo);
                $data['has_welcome_bonus'] = $loyaltyInfo['has_welcome_bonus'];
                $data['is_loyalty_member'] = $loyaltyInfo['is_loyalty_member'];
            }

            $customFields = $data['custom_fields'] ?? [];
            unset($data['custom_fields']);

            // Валидируем кастомные поля
            $validationResult = $this->validationService->validateFields($customFields);

            if (!empty($validationResult['errors'])) {
                throw new \InvalidArgumentException('Invalid custom fields: ' . json_encode($validationResult['errors']));
            }

            $client = Client::create($data);

            // Сохраняем валидированные кастомные поля
            $this->saveValidatedCustomFields($client, $validationResult['validated_fields']);

            $this->accrueRegistrationBonuses($client);

            return $client->load('customFields', 'referrer', 'referrals');
        });
    }

    /**
     * Update client with validated custom fields
     */
    public function update(int $userId, array $data): bool
    {
        return DB::transaction(function () use ($userId, $data) {
            $client = Client::find($userId);

            if (!$client) {
                return false;
            }

            $customFields = $data['custom_fields'] ?? [];
            unset($data['custom_fields']);

            // Валидируем кастомные поля
            $validationResult = $this->validationService->validateFields($customFields);

            if (!empty($validationResult['errors'])) {
                throw new \InvalidArgumentException('Invalid custom fields: ' . json_encode($validationResult['errors']));
            }

            $result = $client->update($data);

            // Сохраняем валидированные кастомные поля
            $this->saveValidatedCustomFields($client, $validationResult['validated_fields']);

            return $result;
        });
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
        return Client::with('customFields', 'referrer')
            ->orderBy('registration_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get clients by referrer
     */
    public function getReferrals(int $referrerId): Collection
    {
        return Client::where('referred_by', $referrerId)
            ->with('customFields', 'referrer')
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
            'custom_fields_count' => $client->customFields()->count(),
        ];
    }

    /**
     * Save custom fields for client
     */
    private function saveCustomFields(Client $client, array $customFields): void
    {
        foreach ($customFields as $field) {
            if (!empty($field['name']) && isset($field['value'])) {
                $client->setCustomField(
                    $field['name'],
                    $field['value'],
                    $field['type'] ?? 'text'
                );
            }
        }
    }

    /**
     * Get custom fields for client
     */
    public function getCustomFields(int $userId): array
    {
        $client = $this->findById($userId);
        return $client ? $client->getCustomFieldsArray() : [];
    }

    /**
     * Update specific custom field
     */
    public function updateCustomField(int $userId, string $fieldName, string $fieldValue, string $fieldType = 'text'): bool
    {
        $client = Client::find($userId);

        if (!$client) {
            return false;
        }

        $client->setCustomField($fieldName, $fieldValue, $fieldType);
        return true;
    }

    /**
     * Delete specific custom field
     */
    public function deleteCustomField(int $userId, string $fieldName): bool
    {
        $client = Client::find($userId);

        if (!$client) {
            return false;
        }

        return $client->customFields()->where('field_name', $fieldName)->delete() > 0;
    }

    /**
     * Get clients with specific custom field value
     */
    public function findByCustomField(string $fieldName, string $fieldValue): Collection
    {
        return Client::whereHas('customFields', function ($query) use ($fieldName, $fieldValue) {
            $query->where('field_name', $fieldName)
                ->where('field_value', $fieldValue);
        })->with('customFields', 'referrer')->get();
    }

    /**
     * Get clients with custom field containing value (LIKE search)
     */
    public function searchByCustomField(string $fieldName, string $searchValue): Collection
    {
        return Client::whereHas('customFields', function ($query) use ($fieldName, $searchValue) {
            $query->where('field_name', $fieldName)
                ->where('field_value', 'LIKE', "%{$searchValue}%");
        })->with('customFields', 'referrer')->get();
    }

    /**
     * Bulk update custom fields for multiple clients
     */
    public function bulkUpdateCustomFields(array $userIds, string $fieldName, string $fieldValue, string $fieldType = 'text'): int
    {
        $updated = 0;

        foreach ($userIds as $userId) {
            if ($this->updateCustomField($userId, $fieldName, $fieldValue, $fieldType)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get all unique custom field names used in the system
     */
    public function getUsedCustomFieldNames(): array
    {
        return CustomClientField::distinct()
            ->pluck('field_name')
            ->toArray();
    }

    /**
     * Get statistics for custom field usage
     */
    public function getCustomFieldsStatistics(): array
    {
        return [
            'total_custom_fields' => CustomClientField::count(),
            'unique_field_names' => CustomClientField::distinct('field_name')->count('field_name'),
            'most_used_fields' => CustomClientField::select('field_name')
                ->selectRaw('COUNT(*) as usage_count')
                ->groupBy('field_name')
                ->orderByDesc('usage_count')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Save validated custom fields
     */
    private function saveValidatedCustomFields(Client $client, array $validatedFields): void
    {
        $validatedFields[] = [
            'type' => 'text',
            'name' => 'courier_id',
            'value' => null,
        ];
        $validatedFields[] = [
            'type' => 'text',
            'name' => 'contract_number',
            'value' => null,
        ];

        foreach ($validatedFields as $field) {
            $field['value'] = $this->setDefaultCustomFieldValues($client, $field);
            $client->setCustomField(
                $field['name'],
                $field['value'],
                $field['type']
            );
        }
    }

    /**
     * Get allowed custom field templates
     */
    public function getAllowedFieldTemplates(): Collection
    {
        return CustomFieldTemplate::active()->ordered()->get();
    }

    /**
     * Create or update custom field template
     */
    public function updateFieldTemplate(array $data): CustomFieldTemplate
    {
        return CustomFieldTemplate::updateOrCreate(
            ['name' => $data['name']],
            $data
        );
    }

    /**
     * Delete custom field template
     */
    public function deleteFieldTemplate(string $name): bool
    {
        return CustomFieldTemplate::where('name', $name)->delete() > 0;
    }

    /**
     * Validate single custom field value
     */
    public function validateFieldValue(string $fieldName, $value): array
    {
        $template = CustomFieldTemplate::active()->where('name', $fieldName)->first();

        if (!$template) {
            return [
                'valid' => false,
                'error' => "Field '{$fieldName}' is not allowed",
            ];
        }

        if (!$template->isValidValue($value)) {
            return [
                'valid' => false,
                'error' => "Invalid value for field '{$template->label}'",
            ];
        }

        return [
            'valid' => true,
            'type' => $template->type,
        ];
    }

    protected function getReferredByFromInvites(int $telegramId, array &$loyaltyInfo): ?int
    {
        $inviteInfo = ReferralInvite::where('telegram_id', $telegramId)->first();
        $refCode = $inviteInfo->referral_code;
        $loyaltyInfo = match($refCode) {
            'CORPORATE' => [
                'is_loyalty_member' => false,
                'has_welcome_bonus' => false,
            ],
            'LOYALTY' => [
                'is_loyalty_member' => true,
                'has_welcome_bonus' => false,
            ],
            default => [
                'is_loyalty_member' => true,
                'has_welcome_bonus' => true,
            ],
        };

        return Client::whereHas('referralInvites', function ($query) use ($telegramId) {
            $query->where('telegram_id', $telegramId);
        })->value('user_id');
    }

    private function accrueRegistrationBonuses(Client $client): void
    {
        if (!$client->has_welcome_bonus || $client->is_loyalty_member) {
            return;
        }

        $referralBonusConfig = BonusSystemConfig::getReferralBonus();
        $welcomeBonus = BonusSystemConfig::getWelcomeBonus();

        if ($client->referred_by) {
            // Клиент пришел по реферальной ссылке
            $refereeBonus = $referralBonusConfig['referee_amount'] ?? 1500;

            // Начисляем бонусы приглашенному
            $client->bonus_balance += $refereeBonus;
            $client->save();

            BonusOperation::create([
                'client_id' => $client->user_id,
                'amount' => $refereeBonus,
                'type' => 'accrual',
                'description' => 'Начисление бонусов за регистрацию по приглашению',
                'metadata' => [
                    'operation_type' => 'referral_registration',
                    'referrer_id' => $client->referred_by,
                    'bonus_amount' => $refereeBonus
                ],
                'is_burnable' => true,
            ]);
        } else {
            // Обычная регистрация
            $client->bonus_balance += $welcomeBonus;
            $client->save();

            BonusOperation::create([
                'client_id' => $client->user_id,
                'amount' => $welcomeBonus,
                'type' => 'accrual',
                'description' => 'Начисление приветственных бонусов',
                'metadata' => [
                    'operation_type' => 'welcome_bonus',
                    'bonus_amount' => $welcomeBonus
                ],
                'is_burnable' => true,
            ]);
        }
    }

    private function setDefaultCustomFieldValues(Client $client, array $field): string
    {
        return match ($field['name']) {
            'courier_id' => $field['value'] ?? sprintf('%s-%d', env('APP_CLIENT_PREFIX', 'КС'), $client->user_id),
            'contract_number' => $field['value'] ?? $client->user_id,
            default => $field['value'],
        };
    }
}

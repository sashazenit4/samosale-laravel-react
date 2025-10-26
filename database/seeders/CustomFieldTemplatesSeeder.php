<?php
// database/seeders/CustomFieldTemplatesSeeder.php

namespace Database\Seeders;

use App\Models\CustomFieldTemplate;
use Illuminate\Database\Seeder;

class CustomFieldTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'contract_number',
                'label' => '№ договора',
                'type' => 'text',
                'validation_rules' => ['max:50'],
                'is_required' => true,
                'sort_order' => 1,
                'description' => 'Номер договора клиента',
            ],
            [
                'name' => 'courier_id',
                'label' => 'ИД курьера',
                'type' => 'text',
                'validation_rules' => ['max:20'],
                'is_required' => false,
                'sort_order' => 2,
                'description' => 'Идентификатор курьера',
            ],
            [
                'name' => 'last_name',
                'label' => 'Фамилия',
                'type' => 'text',
                'validation_rules' => ['required', 'string', 'max:100'],
                'is_required' => true,
                'sort_order' => 3,
                'description' => 'Фамилия клиента',
            ],
            [
                'name' => 'first_name',
                'label' => 'Имя',
                'type' => 'text',
                'validation_rules' => ['required', 'string', 'max:100'],
                'is_required' => true,
                'sort_order' => 4,
                'description' => 'Имя клиента',
            ],
            [
                'name' => 'middle_name',
                'label' => 'Отчество',
                'type' => 'text',
                'validation_rules' => ['string', 'max:100'],
                'is_required' => false,
                'sort_order' => 5,
                'description' => 'Отчество клиента',
            ],
            [
                'name' => 'birth_date',
                'label' => 'Дата рождения',
                'type' => 'date',
                'validation_rules' => ['date', 'before:today'],
                'is_required' => false,
                'sort_order' => 6,
                'description' => 'Дата рождения клиента',
            ],
            [
                'name' => 'phone',
                'label' => 'Телефон',
                'type' => 'text',
                'validation_rules' => ['required', 'string', 'max:20'],
                'is_required' => true,
                'sort_order' => 7,
                'description' => 'Основной телефон клиента',
            ],
            [
                'name' => 'additional_phone',
                'label' => 'Доп телефон',
                'type' => 'text',
                'validation_rules' => ['string', 'max:20'],
                'is_required' => false,
                'sort_order' => 8,
                'description' => 'Дополнительный телефон клиента',
            ],
            [
                'name' => 'relatives_phone',
                'label' => 'Телефон знакомых',
                'type' => 'text',
                'validation_rules' => ['string', 'max:20'],
                'is_required' => false,
                'sort_order' => 9,
                'description' => 'Телефон родственников или знакомых',
            ],
            [
                'name' => 'passport_series',
                'label' => 'Паспорт: серия',
                'type' => 'text',
                'validation_rules' => ['string', 'size:4'],
                'is_required' => false,
                'sort_order' => 10,
                'description' => 'Серия паспорта (4 цифры)',
            ],
            [
                'name' => 'passport_number',
                'label' => 'Паспорт: номер',
                'type' => 'text',
                'validation_rules' => ['string', 'size:6'],
                'is_required' => false,
                'sort_order' => 11,
                'description' => 'Номер паспорта (6 цифр)',
            ],
            [
                'name' => 'passport_issued_by',
                'label' => 'Кем выдан',
                'type' => 'text',
                'validation_rules' => ['string', 'max:255'],
                'is_required' => false,
                'sort_order' => 12,
                'description' => 'Орган, выдавший паспорт',
            ],
            [
                'name' => 'passport_issue_date',
                'label' => 'Когда выдан',
                'type' => 'date',
                'validation_rules' => ['date', 'before:today'],
                'is_required' => false,
                'sort_order' => 13,
                'description' => 'Дата выдачи паспорта',
            ],
            [
                'name' => 'passport_department_code',
                'label' => 'Код подразделения',
                'type' => 'text',
                'validation_rules' => ['string', 'size:7'],
                'is_required' => false,
                'sort_order' => 14,
                'description' => 'Код подразделения (формат: XXX-XXX)',
            ],
            [
                'name' => 'legal_address',
                'label' => 'Адрес прописки',
                'type' => 'text',
                'validation_rules' => ['string', 'max:500'],
                'is_required' => false,
                'sort_order' => 15,
                'description' => 'Адрес регистрации/прописки',
            ],
            [
                'name' => 'actual_address',
                'label' => 'Адрес проживания',
                'type' => 'text',
                'validation_rules' => ['string', 'max:500'],
                'is_required' => false,
                'sort_order' => 16,
                'description' => 'Фактический адрес проживания',
            ],
            [
                'name' => 'registration_date',
                'label' => 'Дата оформления',
                'type' => 'date',
                'validation_rules' => ['date'],
                'is_required' => false,
                'sort_order' => 17,
                'description' => 'Дата оформления договора',
            ],
            [
                'name' => 'courier_service',
                'label' => 'Курьерская служба',
                'type' => 'text',
                'validation_rules' => ['string', 'max:100'],
                'is_required' => false,
                'sort_order' => 18,
                'description' => 'Название курьерской службы',
            ],
            [
                'name' => 'attraction_source',
                'label' => 'Источник привлечения',
                'type' => 'select',
                'options' => [
                    'реклама_интернет',
                    'реклама_улица',
                    'рекомендация',
                    'социальные_сети',
                    'поисковые_системы',
                    'телефонный_звонок',
                    'другое'
                ],
                'validation_rules' => ['string', 'max:50'],
                'is_required' => false,
                'sort_order' => 19,
                'description' => 'Источник привлечения клиента',
            ],
            [
                'name' => 'service_start_date',
                'label' => 'Начало пользования',
                'type' => 'date',
                'validation_rules' => ['date'],
                'is_required' => false,
                'sort_order' => 20,
                'description' => 'Дата начала пользования услугой',
            ],
            [
                'name' => 'service_end_date',
                'label' => 'Конец пользования',
                'type' => 'date',
                'validation_rules' => ['date', 'after:service_start_date'],
                'is_required' => false,
                'sort_order' => 21,
                'description' => 'Дата окончания пользования услугой',
            ],
            [
                'name' => 'serial_number',
                'label' => 'Серийный номер',
                'type' => 'text',
                'validation_rules' => ['string', 'max:100'],
                'is_required' => false,
                'sort_order' => 22,
                'description' => 'Серийный номер оборудования',
            ],
            [
                'name' => 'battery_1',
                'label' => '1й аккумулятор электровелосипеда',
                'type' => 'text',
                'validation_rules' => ['string', 'max:100'],
                'is_required' => false,
                'sort_order' => 23,
                'description' => 'Серийный номер первого аккумулятора',
            ],
            [
                'name' => 'battery_2',
                'label' => '2й аккумулятор электровелосипеда',
                'type' => 'text',
                'validation_rules' => ['string', 'max:100'],
                'is_required' => false,
                'sort_order' => 24,
                'description' => 'Серийный номер второго аккумулятора',
            ],
        ];

        foreach ($templates as $template) {
            CustomFieldTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('Custom field templates seeded successfully!');
        $this->command->info('Total templates: ' . count($templates));
    }
}

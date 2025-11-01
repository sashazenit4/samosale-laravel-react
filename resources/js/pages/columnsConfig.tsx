import { Button, Space, Tag } from 'antd';
import dayjs from 'dayjs';

import { DeleteOutlined, EditOutlined } from '@ant-design/icons';

import utc from 'dayjs/plugin/utc';

dayjs.extend(utc);

export const clientsColumns = (openDrawer: (record: any) => void) => [
    {
        title: '№ договора',
        dataIndex: 'contract_number',
        key: 'contract_number',
        fixed: 'left',
    },
    {
        title: 'ИД курьера',
        dataIndex: 'courier_id',
        key: 'courier_id',
        fixed: 'left',
    },
    {
        title: 'Фамилия',
        dataIndex: 'last_name',
        key: 'last_name',
        fixed: 'left',
    },
    {
        title: 'Имя',
        dataIndex: 'first_name',
        key: 'first_name',
        fixed: 'left',
    },
    {
        title: 'Отчество',
        dataIndex: 'middle_name',
        key: 'middle_name',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Дата рождения',
        dataIndex: 'birth_date',
        key: 'birth_date',
        render: (text: string | null) => text || '-',
    },
    { title: 'Телефон', dataIndex: 'phone_number', key: 'phone_number' },
    {
        title: 'Доп. телефон',
        dataIndex: 'additional_phone',
        key: 'additional_phone',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Телефон знакомых',
        dataIndex: 'relatives_phone',
        key: 'relatives_phone',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Паспорт: серия',
        dataIndex: 'passport_series',
        key: 'passport_series',
    },
    {
        title: 'Паспорт: номер',
        dataIndex: 'passport_number',
        key: 'passport_number',
    },
    {
        title: 'Кем выдан',
        dataIndex: 'passport_issued_by',
        key: 'passport_issued_by',
    },
    {
        title: 'Когда выдан',
        dataIndex: 'passport_issue_date',
        key: 'passport_issue_date',
    },
    {
        title: 'Код подразделения',
        dataIndex: 'passport_department_code',
        key: 'passport_department_code',
    },
    {
        title: 'Адрес прописки',
        dataIndex: 'legal_address',
        key: 'legal_address',
    },
    {
        title: 'Адрес проживания',
        dataIndex: 'actual_address',
        key: 'actual_address',
    },
    {
        title: 'Дата оформления',
        dataIndex: 'registration_date',
        key: 'registration_date',
        render: (date: any) => {
            if (!date) return '—';
            return dayjs.utc(date).format('DD.MM.YYYY');
        },
    },
    {
        title: 'Курьерская служба',
        dataIndex: 'courier_service',
        key: 'courier_service',
    },
    {
        title: 'Источник привлечения',
        dataIndex: 'attraction_source',
        key: 'attraction_source',
        render: (text: string | null) => {
            const options = {
                реклама_интернет: 'Реклама в интернете',
                реклама_улица: 'Уличная реклама',
                рекомендация: 'Рекомендация',
                социальные_сети: 'Социальные сети',
                поисковые_системы: 'Поисковые системы',
                телефонный_звонок: 'Телефонный звонок',
                другое: 'Другое',
            };
            return text ? options[text as keyof typeof options] || text : '-';
        },
    },
    {
        title: 'Начало пользования',
        dataIndex: 'service_start_date',
        key: 'service_start_date',
    },
    {
        title: 'Конец пользования',
        dataIndex: 'service_end_date',
        key: 'service_end_date',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Серийный номер',
        dataIndex: 'serial_number',
        key: 'serial_number',
        render: (text: string | null) => text || '-',
    },
    {
        title: '1-й аккумулятор электровелосипеда',
        dataIndex: 'battery_1',
        key: 'battery_1',
        render: (text: string | null) => text || '-',
    },
    {
        title: '2-й аккумулятор электровелосипеда',
        dataIndex: 'battery_2',
        key: 'battery_2',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right',
        width: 120,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openDrawer(record)}
                />
                <Button
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() => console.log(`Удалить клиента ${record.id}`)}
                />
            </Space>
        ),
    },
];

export const bikeColumns = (openDrawer: (record: any) => void) => [
    {
        title: 'Номер велосипеда',
        dataIndex: 'bike_number',
        key: 'bike_number',
        fixed: 'left',
    },
    {
        title: 'Номер рамы',
        dataIndex: 'frame_number',
        key: 'frame_number',
    },
    {
        title: 'Статус',
        dataIndex: 'status',
        key: 'status',
        render: (text: string) => {
            const statusOptions: Record<
                string,
                { label: string; color: string }
            > = {
                disassembly: { label: 'Разбор', color: 'orange' },
                stolen: { label: 'Угон', color: 'red' },
                free: { label: 'Свободен', color: 'green' },
                repair: { label: 'Ремонт', color: 'blue' },
                rented: { label: 'Аренда', color: 'purple' },
                reserved: { label: 'Бронь', color: 'cyan' },
            };
            const { label, color } = statusOptions[text] || {
                label: text,
                color: 'default',
            };
            return <Tag color={color}>{label}</Tag>;
        },
    },
    {
        title: 'Тип',
        dataIndex: 'type',
        key: 'type',
        render: (text: string) => {
            const typeOptions: Record<string, string> = {
                TRAK: 'ТРАК',
                MOVER: 'МУВЕР',
            };
            return typeOptions[text] || text;
        },
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right',
        width: 120,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openDrawer(record)}
                />
                <Button
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() =>
                        console.log(`Удалить велосипед ${record.id}`)
                    }
                />
            </Space>
        ),
    },
];

export const equipmentColumns = (openDrawer: (record: any) => void) => [
    {
        title: 'Номер',
        dataIndex: 'number',
        key: 'number',
        fixed: 'left',
    },
    {
        title: 'Состояние',
        dataIndex: 'status',
        key: 'status',
        render: (text: string) => {
            const statusOptions: Record<
                string,
                { label: string; color: string }
            > = {
                stolen: { label: 'Угон', color: 'red' },
                free: { label: 'Свободен', color: 'green' },
                rented: { label: 'Аренда', color: 'purple' },
            };
            const { label, color } = statusOptions[text] || {
                label: text,
                color: 'default',
            };
            return <Tag color={color}>{label}</Tag>;
        },
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right',
        width: 120,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openDrawer(record)}
                />
                <Button
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() =>
                        console.log(`Удалить велосипед ${record.id}`)
                    }
                />
            </Space>
        ),
    },
];

export const tariffColumns = (openDrawer: (record: any) => void) => [
    {
        title: 'График платежей',
        children: [
            {
                title: 'Программа',
                dataIndex: 'program',
                key: 'program',
                fixed: 'left',
                render: (text: string) => {
                    const programOptions: Record<
                        string,
                        { label: string; color: string }
                    > = {
                        regular: { label: 'Обычная', color: 'blue' },
                        scooter: { label: 'Самокат', color: 'green' },
                        cooper: { label: 'Купер', color: 'purple' },
                    };
                    const { label, color } = programOptions[text] || {
                        label: text,
                        color: 'default',
                    };
                    return <Tag color={color}>{label}</Tag>;
                },
            },
            {
                title: 'Мощность',
                dataIndex: 'power',
                key: 'power',
            },
        ],
    },
    {
        title: 'По неделям',
        children: [
            {
                title: '1 неделя',
                dataIndex: 'week_1',
                key: 'week_1',
                render: (text: number) => `${text} ₽`,
            },
            {
                title: '2 неделя',
                dataIndex: 'week_2',
                key: 'week_2',
                render: (text: number) => `${text} ₽`,
            },
            {
                title: '3 неделя',
                dataIndex: 'week_3',
                key: 'week_3',
                render: (text: number) => `${text} ₽`,
            },
            {
                title: '4 неделя',
                dataIndex: 'week_4',
                key: 'week_4',
                render: (text: number) => `${text} ₽`,
            },
        ],
    },
    {
        title: 'Сразу',
        children: [
            {
                title: '1 месяц',
                dataIndex: 'month_1',
                key: 'month_1',
                render: (text: number) => `${text} ₽`,
            },
        ],
    },
    {
        title: 'Рассрочка на месяц',
        children: [
            {
                title: '1 месяц, при досрочном расторжении штраф 1000 рублей',
                dataIndex: 'month_1',
                key: 'month_1',
                width: 250,
                render: (text: number) => `${text} ₽`,
            },
        ],
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right',
        width: 120,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openDrawer(record)}
                />
                <Button
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() => console.log(`Удалить тариф ${record.id}`)}
                />
            </Space>
        ),
    },
];

export const paymentsColumns = (openDrawer: (record: any) => void) => [
    {
        title: 'Месяц',
        dataIndex: 'month',
        key: 'month',
        fixed: 'left',
    },
    {
        title: 'Статус',
        dataIndex: 'status',
        key: 'status',
        render: (text: string) => {
            const statusOptions: Record<
                string,
                { label: string; color: string }
            > = {
                paid: { label: 'Оплачено', color: 'green' },
                unpaid: { label: 'Не оплачено', color: 'red' },
            };
            const { label, color } = statusOptions[text] || {
                label: text,
                color: 'default',
            };
            return <Tag color={color}>{label}</Tag>;
        },
    },
    {
        title: 'Год',
        dataIndex: 'year',
        key: 'year',
    },
    {
        title: 'Дата формирования',
        dataIndex: 'formation_date',
        key: 'formation_date',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Дата оплаты',
        dataIndex: 'payment_date',
        key: 'payment_date',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Сумма',
        dataIndex: 'amount',
        key: 'amount',
        render: (text: number) => `${text} ₽`,
    },
    {
        title: 'Тип оплаты',
        dataIndex: 'payment_type',
        key: 'payment_type',
        render: (text: string) => {
            const typeOptions: Record<string, string> = {
                card: 'Картой',
                cash: 'Наличными',
                bank_transfer: 'Банковский перевод',
            };
            return typeOptions[text] || text;
        },
    },
    {
        title: 'Контрагент',
        dataIndex: 'counterparty',
        key: 'counterparty',
    },
    {
        title: 'Статья',
        dataIndex: 'category',
        key: 'category',
    },
    {
        title: 'Назначение платежа',
        dataIndex: 'purpose',
        key: 'purpose',
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right',
        width: 120,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openDrawer(record)}
                />
                <Button
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() => console.log(`Удалить платеж ${record.id}`)}
                />
            </Space>
        ),
    },
];

export const rentsColumns = (openDrawer: (record: any) => void) => [
    {
        title: 'ИД',
        dataIndex: 'id',
        key: 'id',
        fixed: 'left',
    },
    {
        title: 'Клиент',
        dataIndex: 'client',
        key: 'client',
    },
    {
        title: 'Велосипед',
        dataIndex: 'bike',
        key: 'bike',
    },
    {
        title: 'АКБ №1',
        dataIndex: 'battery_1',
        key: 'battery_1',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'АКБ №2',
        dataIndex: 'battery_2',
        key: 'battery_2',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Тариф',
        dataIndex: 'tariff',
        key: 'tariff',
    },
    {
        title: 'Дата начала',
        dataIndex: 'start_date',
        key: 'start_date',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Дата окончания',
        dataIndex: 'end_date',
        key: 'end_date',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Стоимость',
        dataIndex: 'cost',
        key: 'cost',
        render: (text: number) => `${text} ₽`,
    },
    {
        title: 'Оплачено',
        dataIndex: 'paid',
        key: 'paid',
        render: (text: string) => {
            const statusOptions: Record<
                string,
                { label: string; color: string }
            > = {
                paid: { label: 'Оплачено', color: 'green' },
                unpaid: { label: 'Не оплачено', color: 'red' },
            };
            const { label, color } = statusOptions[text] || {
                label: text,
                color: 'default',
            };
            return <Tag color={color}>{label}</Tag>;
        },
    },
    {
        title: 'Примечание',
        dataIndex: 'note',
        key: 'note',
        render: (text: string | null) => text || '-',
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right',
        width: 120,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openDrawer(record)}
                />
                <Button
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() => console.log(`Удалить аренду ${record.id}`)}
                />
                <Button
                    type="text"
                    style={{ color: 'blue' }}
                    onClick={() => console.log(`Удалить аренду ${record.id}`)}
                >
                    Продлить
                </Button>
            </Space>
        ),
    },
];

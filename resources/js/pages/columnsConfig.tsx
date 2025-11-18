import { Button, Space, Tag } from 'antd';
import dayjs from 'dayjs';

import { DeleteOutlined, EditOutlined } from '@ant-design/icons';

import utc from 'dayjs/plugin/utc';

dayjs.extend(utc);

export const clientsColumns = (
    openDrawer: (record: any) => void,
    openConfirmDeleteDrawer: (record: any) => void,
) => [
    {
        title: '№ договора',
        dataIndex: 'custom_fields',
        key: 'contract_number',
        fixed: 'left',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'contract_number')
                ?.field_value,
    },
    {
        title: 'ИД курьера',
        dataIndex: 'custom_fields',
        key: 'courier_id',
        fixed: 'left',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'courier_id')
                ?.field_value,
    },
    {
        title: 'Фамилия',
        dataIndex: 'custom_fields',
        key: 'last_name',
        fixed: 'left',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'last_name')
                ?.field_value,
    },
    {
        title: 'Имя',
        dataIndex: 'custom_fields',
        key: 'first_name',
        fixed: 'left',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'first_name')
                ?.field_value,
    },
    {
        title: 'Отчество',
        dataIndex: 'custom_fields',
        key: 'middle_name',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'middle_name')
                ?.field_value || '-',
    },
    {
        title: 'Дата рождения',
        dataIndex: 'custom_fields',
        key: 'birth_date',
        render: (value: any[]) => {
            const date = (value || []).find(
                (item) => item.field_name === 'birth_date',
            )?.field_value;
            if (!date) return '—';
            return dayjs.utc(date).format('DD.MM.YYYY');
        },
    },
    { title: 'Телефон', dataIndex: 'phone_number', key: 'phone_number' },
    {
        title: 'Доп. телефон',
        dataIndex: 'custom_fields',
        key: 'additional_phone',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'additional_phone')
                ?.field_value || '-',
    },
    {
        title: 'Телефон знакомых',
        dataIndex: 'custom_fields',
        key: 'relatives_phone',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'relatives_phone')
                ?.field_value || '-',
    },
    {
        title: 'Паспорт: серия',
        dataIndex: 'custom_fields',
        key: 'passport_series',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'passport_series')
                ?.field_value || '-',
    },
    {
        title: 'Паспорт: номер',
        dataIndex: 'custom_fields',
        key: 'passport_number',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'passport_number')
                ?.field_value || '-',
    },
    {
        title: 'Кем выдан',
        dataIndex: 'custom_fields',
        key: 'passport_issued_by',
        render: (value: any[]) =>
            (value || []).find(
                (item) => item.field_name === 'passport_issued_by',
            )?.field_value || '-',
    },
    {
        title: 'Когда выдан',
        dataIndex: 'custom_fields',
        key: 'passport_issue_date',
        render: (value: any[]) => {
            const date = (value || []).find(
                (item) => item.field_name === 'passport_issue_date',
            )?.field_value;
            if (!date) return '—';
            return dayjs.utc(date).format('DD.MM.YYYY');
        },
    },
    {
        title: 'Код подразделения',
        dataIndex: 'custom_fields',
        key: 'passport_department_code',
        render: (value: any[]) =>
            (value || []).find(
                (item) => item.field_name === 'passport_department_code',
            )?.field_value || '-',
    },
    {
        title: 'Адрес прописки',
        dataIndex: 'custom_fields',
        key: 'legal_address',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'legal_address')
                ?.field_value || '-',
    },
    {
        title: 'Адрес проживания',
        dataIndex: 'custom_fields',
        key: 'actual_address',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'actual_address')
                ?.field_value || '-',
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
        dataIndex: 'custom_fields',
        key: 'courier_service',
        render: (value: any[]) =>
            (value || []).find((item) => item.field_name === 'courier_service')
                ?.field_value || '-',
    },
    {
        title: 'Источник привлечения',
        dataIndex: 'custom_fields',
        key: 'attraction_source',
        render: (value: any) => {
            const text =
                (value || []).find(
                    (item) => item.field_name === 'attraction_source',
                )?.field_value || '-';
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
        dataIndex: 'custom_fields',
        key: 'service_start_date',
        render: (value: any[]) => {
            const date = (value || []).find(
                (item) => item.field_name === 'service_start_date',
            )?.field_value;
            if (!date) return '—';
            return dayjs.utc(date).format('DD.MM.YYYY');
        },
    },
    {
        title: 'Конец пользования',
        dataIndex: 'custom_fields',
        key: 'service_end_date',
        render: (value: any[]) => {
            const date = (value || []).find(
                (item) => item.field_name === 'service_end_date',
            )?.field_value;
            if (!date) return '—';
            return dayjs.utc(date).format('DD.MM.YYYY');
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
                    onClick={() => openConfirmDeleteDrawer(record)}
                />
            </Space>
        ),
    },
];

export const bikeColumns = (
    openDrawer: (record: any) => void,
    openDelete: (record: any) => void,
) => [
    {
        title: 'Номер велосипеда',
        dataIndex: 'bike_number',
        key: 'bike_number',
        fixed: 'left' as const,
        width: 140,
    },
    {
        title: 'Номер рамы',
        dataIndex: 'frame_number',
        key: 'frame_number',
        width: 140,
    },
    {
        title: 'Статус',
        dataIndex: 'status',
        key: 'status',
        width: 120,
        render: (text: string) => {
            const map: Record<string, { label: string; color: string }> = {
                disassembly: { label: 'Разбор', color: 'orange' },
                stolen: { label: 'Угон', color: 'red' },
                free: { label: 'Свободен', color: 'green' },
                repair: { label: 'Ремонт', color: 'blue' },
                renting: { label: 'Аренда', color: 'purple' },
                reserved: { label: 'Бронь', color: 'cyan' },
            };
            const { label, color } = map[text] || {
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
        width: 100,
        render: (text: 'TRAK' | 'MOVER') => {
            const map = { TRAK: 'TRAK', MOVER: 'MOVER' };
            const color = text === 'TRAK' ? 'blue' : 'green';
            return <Tag color={color}>{map[text] || text}</Tag>;
        },
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right' as const,
        width: 100,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    size="small"
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openDrawer(record)}
                />
                <Button
                    size="small"
                    danger
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() => openDelete(record)}
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

export const tariffColumns = (
    openEdit: (record: any) => void,
    openDelete: (record: any) => void,
) => [
    {
        title: 'Программа',
        dataIndex: 'program',
        key: 'program',
        fixed: 'left' as const,
        width: 120,
        render: (text: string) => {
            const map: Record<string, { label: string; color: string }> = {
                Обычный: { label: 'Обычный', color: 'blue' },
                Самокат: { label: 'Самокат', color: 'green' },
                Купер: { label: 'Купер', color: 'purple' },
            };
            const { label, color } = map[text] || {
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
        width: 100,
        render: (v: number) => `${v} Вт`,
    },
    {
        title: 'По неделям',
        children: [
            {
                title: '1 нед',
                dataIndex: 'price_week1',
                key: 'w1',
                render: (v: number) => `${v} ₽`,
            },
            {
                title: '2 нед',
                dataIndex: 'price_week2',
                key: 'w2',
                render: (v: number) => `${v} ₽`,
            },
            {
                title: '3 нед',
                dataIndex: 'price_week3',
                key: 'w3',
                render: (v: number) => `${v} ₽`,
            },
            {
                title: '4 нед',
                dataIndex: 'price_week4',
                key: 'w4',
                render: (v: number) => `${v} ₽`,
            },
        ],
    },
    {
        title: 'Месяц',
        dataIndex: 'price_month',
        key: 'month',
        render: (v: number) => <strong>{v} ₽</strong>,
    },
    {
        title: 'Статус',
        dataIndex: 'is_active',
        key: 'active',
        render: (active: boolean) => (
            <Tag color={active ? 'green' : 'red'}>
                {active ? 'Активен' : 'Неактивен'}
            </Tag>
        ),
    },
    {
        key: 'actions',
        fixed: 'right' as const,
        width: 100,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    size="small"
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openEdit(record)}
                />
                <Button
                    size="small"
                    danger
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() => openDelete(record)}
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

export const rentsColumns = (
    openEdit: (record: any) => void,
    openExtend: (record: any) => void,
    openDelete: (record: any) => void,
) => [
    {
        title: 'ИД',
        dataIndex: 'id',
        key: 'id',
        fixed: 'left' as const,
        width: 70,
    },
    {
        title: 'Клиент',
        dataIndex: 'client',
        key: 'client',
        width: 180,
    },
    {
        title: 'Велосипед',
        dataIndex: 'bike',
        key: 'bike',
        width: 110,
    },
    {
        title: 'АКБ',
        children: [
            {
                title: 'Емкость',
                dataIndex: 'battery_capacity',
                key: 'capacity',
                render: (v: number | null) => (v ? `${v} Вт·ч` : '-'),
            },
            {
                title: 'Кол-во',
                dataIndex: 'battery_count',
                key: 'count',
                render: (v: number) => v || '-',
            },
        ],
    },
    {
        title: 'Тариф',
        key: 'tariff',
        width: 200,
        render: (_: any, record: any) => {
            const t = record.tariff;
            const type = record.tariff_type;
            const price = record.tariff_price;

            if (!t || !type) return '-';

            const labels: Record<string, string> = {
                '1 week': '1 неделя',
                'next weeks': 'последующие недели',
                month: 'месяц',
            };

            return (
                <div>
                    <div>
                        <strong>{t.program}</strong>
                    </div>
                    <div style={{ color: '#888', fontSize: '0.85em' }}>
                        <Tag color="default" style={{ margin: 0 }}>
                            {labels[type] || type}: {price} ₽
                        </Tag>
                    </div>
                </div>
            );
        },
    },
    {
        title: 'Статус',
        children: [
            {
                title: 'Аренда',
                dataIndex: 'is_completed',
                key: 'completed',
                render: (v: boolean) => (
                    <Tag color={v ? 'blue' : 'green'}>
                        {v ? 'Завершена' : 'Активна'}
                    </Tag>
                ),
            },
            {
                title: 'Оплата',
                dataIndex: 'paid',
                key: 'paid',
                render: (v: string) => (
                    <Tag color={v === 'paid' ? 'green' : 'red'}>
                        {v === 'paid' ? 'Оплачено' : 'Не оплачено'}
                    </Tag>
                ),
            },
        ],
    },
    {
        title: 'Даты',
        children: [
            {
                title: 'Начало',
                dataIndex: 'start_date',
                key: 'start',
                render: (date: string | null) =>
                    date ? dayjs.utc(date).format('DD.MM.YYYY') : '-',
            },
            {
                title: 'Завершение',
                dataIndex: 'end_date',
                key: 'end',
                render: (date: string | null) =>
                    date ? dayjs.utc(date).format('DD.MM.YYYY') : '-',
            },
        ],
    },
    {
        title: 'Стоимость',
        dataIndex: 'cost',
        key: 'cost',
        render: (v: number) => <strong>{v} ₽</strong>,
    },
    {
        title: 'Примечание',
        dataIndex: 'note',
        key: 'note',
        render: (t: string | null) => t || '-',
    },
    {
        key: 'actions',
        fixed: 'right' as const,
        width: 150,
        render: (_: any, record: any) => (
            <Space>
                <Button
                    size="small"
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => openEdit(record)}
                />
                <Button
                    size="small"
                    danger
                    type="text"
                    icon={<DeleteOutlined />}
                    onClick={() => openDelete(record)}
                />
                <Button
                    size="small"
                    type="link"
                    onClick={() => openExtend(record)}
                >
                    Продлить
                </Button>
            </Space>
        ),
    },
];

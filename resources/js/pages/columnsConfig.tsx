import { Button, Col, Row, Space, Tag } from 'antd';
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
            return dayjs(date).format('DD.MM.YYYY');
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
            return dayjs(date).format('DD.MM.YYYY');
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
            return dayjs(date).format('DD.MM.YYYY');
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
                    (item: { field_name: string }) =>
                        item.field_name === 'attraction_source',
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
        title: 'Дата оформления',
        dataIndex: 'custom_fields',
        key: 'issue_date',
        render: (value: any[]) => {
            const date = (value || []).find(
                (item) => item.field_name === 'issue_date',
            )?.field_value;
            if (!date) return '—';
            return dayjs(date).format('DD.MM.YYYY');
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
            return dayjs(date).format('DD.MM.YYYY');
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
            return dayjs(date).format('DD.MM.YYYY');
        },
    },
    {
        title: 'Бонусный баланс',
        dataIndex: 'bonus_balance',
        key: 'bonus_balance',
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
        render: (text: any) => {
            const map: any = {
                MotorPulse: { color: 'blue', text: 'МоторПульс' },
                MotorRave: { color: 'green', text: 'МоторРейв' },
                MotorFlow: { color: 'magenta', text: 'МоторФлоу' },
                MotorStream: { color: 'purple', text: 'МоторСтрим' },
                MotorGlide: { color: 'orange', text: 'МоторГлайд' },
            };
            return (
                <Tag color={map[text]?.color}>{map[text]?.text || text}</Tag>
            );
        },
    },
    {
        title: 'Свойство 1',
        dataIndex: 'property_1',
        key: 'property_1',
        width: 120,
    },
    {
        title: 'Свойство 2',
        dataIndex: 'property_2',
        key: 'property_2',
        width: 120,
    },
    {
        title: 'Свойство 3',
        dataIndex: 'property_3',
        key: 'property_3',
        width: 120,
    },
    {
        title: 'Свойство 4',
        dataIndex: 'property_4',
        key: 'property_4',
        width: 120,
    },
    {
        title: 'Свойство 5',
        dataIndex: 'property_5',
        key: 'property_5',
        width: 120,
    },
    {
        title: 'Свойство 6',
        dataIndex: 'property_6',
        key: 'property_6',
        width: 120,
    },
    {
        title: 'Свойство 7',
        dataIndex: 'property_7',
        key: 'property_7',
        width: 120,
    },
    {
        title: 'Свойство 8',
        dataIndex: 'property_8',
        key: 'property_8',
        width: 120,
    },
    {
        title: 'Свойство 9',
        dataIndex: 'property_9',
        key: 'property_9',
        width: 120,
    },
    {
        title: 'Свойство 10',
        dataIndex: 'property_10',
        key: 'property_10',
        width: 120,
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
        title: 'Емкость',
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

// resources/js/pages/payments/columns.tsx

import 'dayjs/locale/ru';
dayjs.locale('ru');

interface PaymentRecord {
    id: number;
    month_ru?: string;
    month?: string;
    year: string;
    total_amount: number;
    paid_amount: number;
    remaining_amount: number;
    payment_type?: 'card' | 'cash' | 'bank_transfer';
    payment_type_ru?: string;
    article_ru?: string;
    purpose?: string;
    status: 'paid' | 'unpaid';
    status_ru?: string;
    generated_at?: string | null;
    сreated_at?: string | null;
    paid_at?: string | null;
    client?: {
        full_name?: string;
        name?: string;
        phone_number?: string;
    } | null;
}

const paymentTypeMap: Record<'card' | 'cash' | 'bank_transfer', string> = {
    card: 'Картой',
    cash: 'Наличными',
    bank_transfer: 'Перевод',
};

export const paymentsColumns = (
    onEdit: (record: PaymentRecord) => void,
    onDelete: (id: number) => void,
) => [
    {
        title: 'Месяц',
        width: 100,
        fixed: 'left' as const,
        render: (_: any, record: PaymentRecord) =>
            record.month_ru || record.month || '—',
    },
    {
        title: 'Клиент',
        width: 230,
        render: (_: any, record: PaymentRecord) => (
            <div>
                <div className="font-medium">
                    {record.client?.full_name || record.client?.name || '—'}
                </div>
                {record.client?.phone_number && (
                    <div className="text-xs text-gray-500">
                        {record.client.phone_number}
                    </div>
                )}
            </div>
        ),
    },
    {
        title: 'Статус',
        width: 140,
        render: (_: any, record: PaymentRecord) => {
            const label =
                record.status_ru ||
                (record.status === 'paid' ? 'Оплачено' : 'Не оплачено');
            const color = record.status === 'paid' ? 'green' : 'red';
            return <Tag color={color}>{label}</Tag>;
        },
    },
    {
        title: 'Год',
        dataIndex: 'year',
        width: 80,
        align: 'center' as const,
    },
    {
        title: 'Сумма',
        width: 140,
        align: 'right' as const,
        render: (_: any, record: PaymentRecord) => (
            <div>
                <div className="font-medium">
                    {record.total_amount.toLocaleString()} ₽
                </div>
                {record.remaining_amount > 0 && (
                    <div className="text-xs font-medium text-red-600">
                        Осталось: {record.remaining_amount.toLocaleString()} ₽
                    </div>
                )}
            </div>
        ),
    },
    {
        title: 'Оплачено',
        width: 110,
        align: 'right' as const,
        render: (_: any, record: PaymentRecord) => (
            <span
                className={
                    record.paid_amount > 0 ? 'font-medium text-green-600' : ''
                }
            >
                {record.paid_amount.toLocaleString()} ₽
            </span>
        ),
    },
    {
        title: 'Тип оплаты',
        width: 130,
        render: (_: any, record: PaymentRecord) =>
            record.payment_type_ru ||
            (record.payment_type ? paymentTypeMap[record.payment_type] : '—'),
    },
    {
        title: 'Статья',
        dataIndex: 'article_ru',
        width: 160,
        render: (text: string) => text || '—',
    },
    {
        title: 'Назначение',
        dataIndex: 'purpose',
        width: 300,
        ellipsis: { showTitle: true },
        render: (text: string) => text || '—',
    },
    {
        title: 'Плановая дата оплаты',
        width: 140,
        render: (_: any, record: PaymentRecord) =>
            record.generated_at
                ? dayjs(record.generated_at).format('DD MMM YYYY')
                : '—',
    },
    {
        title: 'Сформировано',
        width: 140,
        render: (_: any, record: PaymentRecord) =>
            record.generated_at
                ? dayjs(record.сreated_at).format('DD MMM YYYY')
                : '—',
    },
    {
        title: 'Оплачен',
        width: 150,
        render: (_: any, record: PaymentRecord) =>
            record.paid_at
                ? dayjs(record.paid_at).format('DD MMM YYYY HH:mm')
                : '—',
    },
    {
        title: 'Действия',
        key: 'actions',
        fixed: 'right' as const,
        width: 100,
        render: (_: any, record: PaymentRecord) => (
            <Space size="small">
                <Button
                    type="text"
                    size="small"
                    icon={<EditOutlined />}
                    onClick={() => onEdit(record)}
                />
                {/* Раскомментируй, когда включишь удаление */}
                {/* <Popconfirm
                    title="Удалить платёж?"
                    onConfirm={() => onDelete(record.id)}
                    okText="Да"
                    cancelText="Нет"
                >
                    <Button type="text" size="small" danger icon={<DeleteOutlined />} />
                </Popconfirm> */}
            </Space>
        ),
    },
];

export const rentsColumns = (
    openEdit: (record: any) => void,
    openExtend: (record: any) => void,
    openDelete: (record: any) => void,
    openPaidModal: (record: any) => void,
    getDocument: (id: string) => void,
    getDocumentPDF: (id: string) => void,
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
        dataIndex: 'full_name',
        key: 'full_name',
        width: 180,
        render: (_: any, record: any) => record?.client?.full_name || '-',
    },
    {
        title: 'Велосипед',
        dataIndex: 'bike',
        key: 'bike',
        width: 110,
        render: (_: any, record: any) => record?.bike?.bike_number || '-',
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
                dataIndex: 'batteries_count',
                key: 'batteries_count',
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
            const type = t.program;
            const price = record.tariff;

            if (!t || !type) return '-';

            const labels: Record<string, string> = {
                price_week1: '1 неделя',
                price_week2: 'последующие недели',
                price_month: 'месяц',
            };

            return (
                <div>
                    <div>
                        <strong>
                            {t.program} {t.power}W
                        </strong>
                    </div>
                    <div style={{ color: '#888', fontSize: '0.85em' }}>
                        {Object.keys(labels).map((type) => (
                            <Tag color="default" style={{ margin: 0 }}>
                                {labels[type]}: {price[type]}₽
                            </Tag>
                        ))}
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
                dataIndex: 'status',
                key: 'status',
                render: (v: any) => (
                    <Tag
                        color={
                            v === 'active'
                                ? 'green'
                                : v === 'completed'
                                  ? 'blue'
                                  : 'magenta'
                        }
                    >
                        {v === 'active'
                            ? 'Активна'
                            : v === 'completed'
                              ? 'Завершена'
                              : 'Отменена / завершена заранее'}
                    </Tag>
                ),
            },
            {
                title: 'Оплата',
                dataIndex: 'paid_status',
                key: 'paid_status',
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
                    date ? dayjs(date).format('DD.MM.YYYY') : '-',
            },
            {
                title: 'Плановое завершение',
                dataIndex: 'planned_end_date',
                key: 'end',
                render: (date: string | null) =>
                    date ? dayjs(date).format('DD.MM.YYYY') : '-',
            },
            {
                title: 'Фактическое завершение',
                dataIndex: 'actual_end_date',
                key: 'end',
                render: (date: string | null) =>
                    date ? dayjs(date).format('DD.MM.YYYY') : '-',
            },
        ],
    },
    {
        title: 'Стоимость',
        dataIndex: 'total_cost',
        key: 'total_cost',
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
        width: 250,
        render: (_: any, record: any) => (
            <Row gutter={[30, 30]}>
                <Col span={6}>
                    {record.status === 'active' && (
                        <Button
                            size="small"
                            type="text"
                            icon={<EditOutlined />}
                            onClick={() => openEdit(record)}
                        />
                    )}
                </Col>
                <Col span={18}>
                    {record.status === 'active' && (
                        <Button
                            size="small"
                            onClick={() => openPaidModal(record)}
                            type="link"
                            style={{
                                whiteSpace: 'normal',
                                textAlign: 'left',
                                height: '100% ',
                                paddingBlock: 4,
                            }}
                        >
                            Отметить как оплаченное
                        </Button>
                    )}
                </Col>

                <Col span={6}>
                    <Button
                        size="small"
                        danger
                        type="text"
                        icon={<DeleteOutlined />}
                        onClick={() => openDelete(record)}
                    />
                </Col>
                <Col span={18}>
                    {record.status === 'active' && (
                        <Button
                            size="small"
                            type="link"
                            onClick={() => openExtend(record)}
                        >
                            Продлить
                        </Button>
                    )}
                </Col>
                <Col span={12}>
                    <Button
                        size="small"
                        type="link"
                        onClick={() => getDocument(record.id)}
                    >
                        Договор
                    </Button>
                </Col>
                <Col span={12}>
                    <Button
                        size="small"
                        type="link"
                        onClick={() => getDocumentPDF(record.id)}
                    >
                        Договор PDF
                    </Button>
                </Col>
            </Row>
        ),
    },
];

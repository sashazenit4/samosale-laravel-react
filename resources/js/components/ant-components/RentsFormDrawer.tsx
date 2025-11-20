import { SearchOutlined } from '@ant-design/icons';
import {
    Input as AntInput,
    Button,
    DatePicker,
    Drawer,
    Form,
    Input,
    InputNumber,
    Select,
    Space,
    Table,
    Typography,
} from 'antd';
import dayjs from 'dayjs';
import React, { useMemo, useState } from 'react';

const { Text } = Typography;

// === ТИПЫ ===
interface Client {
    user_id: number;
    first_name: string;
    last_name: string;
    middle_name?: string;
    tg_username?: string;
    custom_fields: any;
}

interface Bike {
    id: number;
    bike_number: string;
    frame_number: string;
    status: 'free' | 'renting';
}

interface Tariff {
    id: number;
    program: string;
    power: any;
    price_week1: number;
    price_week2: number;
    price_month: number;
}

interface RentFormData {
    client_id: number;
    bike_id: number;
    tariff_id: number;
    battery_capacity: number | null;
    battery_count: number;
    planned_end_date: any;
    start_date: dayjs.Dayjs;
    rental_period: '1week' | '2weeks' | '3weeks' | 'month';
    note: string | null;
}

interface RentFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: RentFormData) => void;
    clients: Client[];
    bikes: Bike[];
    tariffs: Tariff[];
    initialValues?: Partial<RentFormData>;
    isEditing: boolean;
}

const ClientTable: React.FC<{
    clients: Client[];
    selectedId: number | null;
    onSelect: (id: number) => void;
}> = ({ clients, selectedId, onSelect }) => {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        return clients.filter(
            (c) =>
                `${
                    (c?.custom_fields || []).find(
                        (item: any) => item.field_name === 'last_name',
                    )?.field_value
                } ${
                    (c?.custom_fields || []).find(
                        (item: any) => item.field_name === 'first_name',
                    )?.field_value
                } ${
                    (c?.custom_fields || []).find(
                        (item: any) => item.field_name === 'middle_name',
                    )?.field_value || ''
                }`
                    .toLowerCase()
                    .includes(search.toLowerCase()) ||
                (c.tg_username || '')
                    .toLowerCase()
                    .includes(search.toLowerCase()),
        );
    }, [clients, search]);

    const columns = [
        {
            title: 'ФИО',
            dataIndex: 'custom_fields',
            key: 'fio',
            render: (value: any[]) =>
                `${
                    (value || []).find(
                        (item) => item.field_name === 'last_name',
                    )?.field_value ?? ''
                } ${
                    (value || []).find(
                        (item) => item.field_name === 'first_name',
                    )?.field_value ?? ''
                } ${
                    (value || []).find(
                        (item) => item.field_name === 'middle_name',
                    )?.field_value ?? ''
                }`,
        },
        {
            title: 'TG Username',
            dataIndex: 'tg_username',
            render: (v: string) =>
                v ? `@${v}` : <Text type="secondary">—</Text>,
        },
    ];

    return (
        <Space direction="vertical" style={{ width: '100%' }}>
            <AntInput
                placeholder="Поиск по ФИО или @username"
                prefix={<SearchOutlined />}
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                allowClear
            />
            <Table
                columns={columns}
                dataSource={filtered}
                rowKey="user_id"
                pagination={false}
                size="small"
                scroll={{ y: 240 }}
                rowSelection={{
                    type: 'radio',
                    selectedRowKeys: selectedId ? [selectedId] : [],
                    onChange: (selectedKeys) => {
                        onSelect(selectedKeys[0] as number);
                    },
                }}
            />
        </Space>
    );
};

const BikeTable: React.FC<{
    bikes: Bike[];
    selectedId: number | null;
    onSelect: (id: number) => void;
}> = ({ bikes, selectedId, onSelect }) => {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        return bikes.filter(
            (b) =>
                b.bike_number.toLowerCase().includes(search.toLowerCase()) ||
                b.frame_number.toLowerCase().includes(search.toLowerCase()),
        );
    }, [bikes, search]);

    const columns = [
        { title: 'Номер', dataIndex: 'bike_number', key: 'number' },
        { title: 'Рама', dataIndex: 'frame_number', key: 'frame' },
        {
            title: 'Статус',
            dataIndex: 'status',
            key: 'status',
            render: (v: string) => {
                const labels: Record<string, string> = {
                    free: 'Свободен',
                    renting: 'В аренде',
                };
                const colors: Record<string, string> = {
                    free: 'success',
                    renting: 'warning',
                };
                return <Text type={colors[v] as any}>{labels[v]}</Text>;
            },
        },
    ];

    return (
        <Space direction="vertical" style={{ width: '100%' }}>
            <AntInput
                placeholder="Поиск по номеру или раме"
                prefix={<SearchOutlined />}
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                allowClear
            />
            <Table
                columns={columns}
                dataSource={filtered}
                rowKey="id"
                pagination={false}
                size="small"
                scroll={{ y: 240 }}
                rowSelection={{
                    type: 'radio',
                    selectedRowKeys: selectedId ? [selectedId] : [],
                    onChange: (selectedKeys) => {
                        const id = selectedKeys[0] as number;
                        const bike = bikes.find((b) => b.id === id);
                        if (bike?.status === 'free') {
                            onSelect(id);
                        }
                    },
                    getCheckboxProps: (record: Bike) => ({
                        disabled: record.status !== 'free',
                    }),
                }}
            />
        </Space>
    );
};

const RentFormDrawer: React.FC<RentFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    clients,
    bikes,
    tariffs,
    initialValues,
    isEditing,
}) => {
    const [form] = Form.useForm<any>();
    const [clientId, setClientId] = useState<number | null>(
        initialValues?.client_id || null,
    );
    const [bikeId, setBikeId] = useState<number | null>(
        initialValues?.bike_id || null,
    );

    const startDate = Form.useWatch('start_date', form);
    const rentalPeriod = Form.useWatch('rental_period', form);
    const status = Form.useWatch('status', form);

    const endDate = useMemo(() => {
        if (!startDate || !rentalPeriod) return null;
        const days = rentalPeriod === 'month' ? 30 : parseInt(rentalPeriod) * 7;
        return startDate.add(days, 'day');
    }, [startDate, rentalPeriod]);

    const parseDate = (dateString: string | null | undefined) => {
        if (!dateString) return null;
        const parsed = dayjs(dateString);
        return parsed.isValid() ? parsed : null;
    };

    const defaultValues = {
        ...initialValues,
        start_date: isEditing
            ? (parseDate(initialValues?.start_date as unknown as string) ??
              dayjs())
            : dayjs(),
        planned_end_date: isEditing
            ? parseDate(initialValues?.planned_end_date as string)
            : null,
        actual_end_date: isEditing
            ? parseDate(initialValues?.planned_end_date as string)
            : null,
        rental_period: '1week',
        battery_count: 1,
    };

    return (
        <Drawer
            title={isEditing ? 'Редактировать аренду' : 'Создать аренду'}
            width={560}
            onClose={() => {
                onClose();
                form.resetFields();
                setClientId(null);
                setBikeId(null);
            }}
            open={visible}
            bodyStyle={{ paddingBottom: 80 }}
            footer={
                <div style={{ textAlign: 'right' }}>
                    <Button
                        onClick={() => {
                            onClose();
                            form.resetFields();
                        }}
                        style={{ marginRight: 8 }}
                    >
                        Отмена
                    </Button>
                    <Button
                        type="primary"
                        onClick={() => {
                            // if (!clientId || !bikeId || !isEditing) {
                            //     message.error('Выберите клиента и велосипед');
                            //     return;
                            // }
                            form.setFieldsValue({
                                client_id: clientId,
                                bike_id: bikeId,
                            });
                            form.submit();
                        }}
                    >
                        {isEditing ? 'Сохранить' : 'Создать'}
                    </Button>
                </div>
            }
        >
            <Form
                form={form}
                layout="vertical"
                initialValues={defaultValues}
                onFinish={(values) => {
                    onSubmit({
                        ...values,
                        client_id: clientId!,
                        bike_id: bikeId!,
                        planned_end_date: isEditing
                            ? form.getFieldValue('planned_end_date')
                            : endDate,
                        actual_end_date:
                            form.getFieldValue('status') !== 'active'
                                ? form.getFieldValue('actual_end_date')
                                : null,
                    });
                    form.resetFields();
                }}
            >
                {!isEditing && (
                    <Form.Item label="Клиент" required>
                        <ClientTable
                            clients={clients}
                            selectedId={clientId}
                            onSelect={setClientId}
                        />
                        {!clientId && (
                            <Text type="danger">Выберите клиента</Text>
                        )}
                    </Form.Item>
                )}
                {!isEditing && (
                    <Form.Item label="Велосипед" required>
                        <BikeTable
                            bikes={bikes}
                            selectedId={bikeId}
                            onSelect={setBikeId}
                        />
                        {!bikeId && (
                            <Text type="danger">Выберите велосипед</Text>
                        )}
                    </Form.Item>
                )}

                {/* ТАРИФЫ */}
                {!isEditing && (
                    <Form.Item
                        name="tariff_id"
                        label="Тариф"
                        rules={[{ required: true }]}
                    >
                        <Select
                            placeholder="Выберите тариф"
                            style={{ height: 50 }}
                            dropdownStyle={{ minWidth: 300 }}
                            options={tariffs.map((t) => ({
                                value: t.id,
                                label: (
                                    <div
                                        style={{
                                            whiteSpace: 'normal',
                                            lineHeight: 1.4,
                                        }}
                                    >
                                        <div>
                                            <strong>
                                                {t.program} {t.power}W
                                            </strong>
                                        </div>
                                        <Text
                                            type="secondary"
                                            style={{
                                                fontSize: '0.8em',
                                                display: 'block',
                                            }}
                                        >
                                            {t.price_week1}₽/1н, {t.price_week2}
                                            ₽/посл.н, {t.price_month}₽/мес
                                        </Text>
                                    </div>
                                ),
                            }))}
                        />
                    </Form.Item>
                )}

                <div
                    style={{ borderTop: '1px solid #f0f0f0', margin: '16px 0' }}
                />

                <Space direction="vertical" style={{ width: '100%' }}>
                    <Text strong>Детали аренды</Text>

                    {!isEditing && (
                        <Space>
                            <Form.Item
                                name="battery_capacity"
                                label="Емкость АКБ (Вт·ч)"
                                style={{ marginBottom: 0, flex: 1 }}
                            >
                                <Input
                                    style={{ width: '100%' }}
                                    placeholder="21"
                                />
                            </Form.Item>
                            <Form.Item
                                name="batteries_count"
                                label="Кол-во"
                                style={{ marginBottom: 0, width: 100 }}
                            >
                                <InputNumber min={0} max={10} />
                            </Form.Item>
                        </Space>
                    )}

                    {!isEditing && (
                        <Form.Item
                            name="start_date"
                            label="Дата начала"
                            rules={[{ required: true }]}
                        >
                            <DatePicker
                                style={{ width: '100%' }}
                                format="DD.MM.YYYY"
                            />
                        </Form.Item>
                    )}

                    {isEditing && (
                        <Form.Item
                            name="planned_end_date"
                            label="Дата окончания"
                            rules={[{ required: true }]}
                        >
                            <DatePicker
                                style={{ width: '100%' }}
                                format="DD.MM.YYYY"
                            />
                        </Form.Item>
                    )}

                    {!isEditing && (
                        <Form.Item
                            name="rental_period"
                            label="Срок аренды"
                            rules={[{ required: true }]}
                        >
                            <Select>
                                <Select.Option value="1week">
                                    1 неделя
                                </Select.Option>
                                <Select.Option value="2weeks">
                                    2 недели
                                </Select.Option>
                                <Select.Option value="3weeks">
                                    3 недели
                                </Select.Option>
                                <Select.Option value="4weeks">
                                    4 недели
                                </Select.Option>
                                <Select.Option value="month">
                                    1 месяц
                                </Select.Option>
                            </Select>
                        </Form.Item>
                    )}

                    {isEditing && (
                        <Form.Item
                            name="status"
                            label="Статус аренды"
                            rules={[{ required: true }]}
                        >
                            <Select>
                                <Select.Option value="active">
                                    Активна
                                </Select.Option>
                                <Select.Option value="completed">
                                    Завершена
                                </Select.Option>
                                <Select.Option value="completed_early">
                                    Завершена заранее
                                </Select.Option>
                                <Select.Option value="cancelled">
                                    Отменена
                                </Select.Option>
                            </Select>
                        </Form.Item>
                    )}
                    {isEditing &&
                        ['completed_early', 'completed', 'cancelled'].includes(
                            status,
                        ) && (
                            <Form.Item
                                name="actual_end_date"
                                label="Фактическая дата окончания"
                                rules={[{ required: true }]}
                            >
                                <DatePicker
                                    style={{ width: '100%' }}
                                    format="DD.MM.YYYY"
                                />
                            </Form.Item>
                        )}
                    {isEditing &&
                        ['completed_early', 'cancelled'].includes(status) && (
                            <Form.Item
                                name="completion_type"
                                label="Причина досрочной аренды"
                                rules={[{ required: true }]}
                            >
                                <Select>
                                    <Select.Option value="bike_change">
                                        Изменен велосипед
                                    </Select.Option>
                                    <Select.Option value="cancellation">
                                        Отмена со стороны курьера
                                    </Select.Option>
                                </Select>
                            </Form.Item>
                        )}
                    {!isEditing && endDate && (
                        <div style={{ marginBottom: 16 }}>
                            <Text type="secondary">
                                <strong>Дата окончания:</strong>{' '}
                                {endDate.format('DD.MM.YYYY')}
                            </Text>
                        </div>
                    )}

                    <Form.Item name="note" label="Примечание">
                        <Input.TextArea
                            rows={3}
                            placeholder="Дополнительная информация..."
                        />
                    </Form.Item>
                </Space>
            </Form>
        </Drawer>
    );
};

export default RentFormDrawer;

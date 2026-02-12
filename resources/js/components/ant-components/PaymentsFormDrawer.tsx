// components/ant-components/PaymentsFormDrawer.tsx

import { Button, DatePicker, Drawer, Form, Input, Select } from 'antd';
import dayjs from 'dayjs';
import React from 'react';

interface PaymentFormData {
    client_id: number;
    month: string; // 'july', 'august' и т.д.
    year: number;
    generated_at: string | null; // дата формирования
    paid_at: string | null; // дата оплаты
    total_amount: number;
    payment_type: 'cash' | 'card' | 'bank_transfer';
    article: string; // 'bike_repair', 'rent' и т.д.
    purpose: string;
    status: 'paid' | 'unpaid';
}

interface PaymentFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: PaymentFormData) => void;
    initialValues?: Partial<PaymentFormData> & { id?: number };
    isEditing: boolean;
    clients: any;
}

const monthOptions = [
    { value: 'january', label: 'Январь' },
    { value: 'february', label: 'Февраль' },
    { value: 'march', label: 'Март' },
    { value: 'april', label: 'Апрель' },
    { value: 'may', label: 'Май' },
    { value: 'june', label: 'Июнь' },
    { value: 'july', label: 'Июль' },
    { value: 'august', label: 'Август' },
    { value: 'september', label: 'Сентябрь' },
    { value: 'october', label: 'Октябрь' },
    { value: 'november', label: 'Ноябрь' },
    { value: 'december', label: 'Декабрь' },
];

const articleOptions = [
    { value: 'bike_repair', label: 'Ремонт велосипеда' },
    { value: 'fine', label: 'Штраф' },
];

const articleOptionsWithRental = [
    { value: 'bike_repair', label: 'Ремонт велосипеда' },
    { value: 'bike_rental', label: 'Аренда' },
    { value: 'fine', label: 'Штраф' },
];

const PaymentFormDrawer: React.FC<PaymentFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    initialValues,
    isEditing,
    clients,
}) => {
    const [form] = Form.useForm<PaymentFormData>();

    // При редактировании — подставляем значения, при создании — только дату формирования
    React.useEffect(() => {
        if (visible) {
            if (isEditing && initialValues) {
                form.setFieldsValue({
                    ...initialValues,
                    generated_at: initialValues.generated_at
                        ? dayjs(initialValues.generated_at)
                        : dayjs(),
                    paid_at: initialValues.paid_at
                        ? dayjs(initialValues.paid_at)
                        : dayjs(),
                });
            } else {
                form.resetFields();
                form.setFieldsValue({
                    generated_at: dayjs(),
                    year: dayjs().year(),
                    status: 'unpaid',
                });
            }
        }
    }, [visible, initialValues, isEditing, form]);

    const handleSubmit = (values: any) => {
        onSubmit({
            ...values,
            generated_at:
                values.generated_at?.format('YYYY-MM-DD HH:mm:ss') || null,
            paid_at: values.paid_at?.format('YYYY-MM-DD HH:mm:ss') || null,
            total_amount: Number(values.total_amount),
        });
        form.resetFields();
    };

    return (
        <Drawer
            title={isEditing ? 'Редактировать платёж' : 'Создать платёж'}
            width={480}
            open={visible}
            onClose={onClose}
            destroyOnClose
            footer={
                <div style={{ textAlign: 'right' }}>
                    <Button onClick={onClose} style={{ marginRight: 8 }}>
                        Отмена
                    </Button>
                    <Button type="primary" onClick={() => form.submit()}>
                        {isEditing ? 'Сохранить' : 'Создать'}
                    </Button>
                </div>
            }
        >
            <Form form={form} layout="vertical" onFinish={handleSubmit}>
                <Form.Item
                    name="client_id"
                    label="Клиент"
                    rules={[{ required: true, message: 'Выберите клиента' }]}
                >
                    <Select
                        showSearch
                        placeholder="Начните вводить ФИО или телефон"
                        optionFilterProp="label"
                        options={clients.map((c) => ({
                            value: c.user_id,
                            label: `${
                                (c?.custom_fields || []).find(
                                    (item: any) =>
                                        item.field_name === 'last_name',
                                )?.field_value || ''
                            } ${
                                (c?.custom_fields || []).find(
                                    (item: any) =>
                                        item.field_name === 'first_name',
                                )?.field_value || ''
                            } ${
                                (c?.custom_fields || []).find(
                                    (item: any) =>
                                        item.field_name === 'middle_name',
                                )?.field_value || ''
                            }`,
                        }))}
                    />
                </Form.Item>

                <Form.Item
                    name="month"
                    label="Месяц"
                    rules={[{ required: true }]}
                >
                    <Select options={monthOptions} />
                </Form.Item>

                <Form.Item name="year" label="Год" rules={[{ required: true }]}>
                    <Input type="number" min={2020} max={2030} />
                </Form.Item>

                <Form.Item
                    name="generated_at"
                    label="Дата формирования"
                    rules={[{ required: true, message: 'Укажите дату' }]}
                >
                    <DatePicker
                        disabled
                        format="DD.MM.YYYY"
                        style={{ width: '100%' }}
                    />
                </Form.Item>

                <Form.Item name="paid_at" label="Дата оплаты">
                    <DatePicker
                        disabled={!isEditing}
                        format="DD.MM.YYYY"
                        style={{ width: '100%' }}
                    />
                </Form.Item>

                <Form.Item
                    name="total_amount"
                    label="Сумма"
                    rules={[{ required: true, message: 'Введите сумму' }]}
                >
                    <Input type="number" suffix="₽" step="0.01" />
                </Form.Item>
                {isEditing && (
                    <Form.Item
                        name="paid_amount"
                        label="Оплачено"
                        rules={[{ required: true, message: 'Введите сумму' }]}
                    >
                        <Input type="number" suffix="₽" step="0.01" />
                    </Form.Item>
                )}

                <Form.Item
                    name="payment_type"
                    label="Тип оплаты"
                    rules={[{ required: true }]}
                >
                    <Select
                        options={[
                            { value: 'cash', label: 'Наличными' },
                            { value: 'cashless', label: 'Картой' },
                            {
                                value: 'mixed',
                                label: 'Смешанная',
                            },
                            {
                                value: 'corporate',
                                label: 'Корпоративная',
                            },
                        ]}
                    />
                </Form.Item>

                <Form.Item
                    name="article"
                    label="Статья"
                    rules={[{ required: true }]}
                >
                    <Select
                        disabled={
                            isEditing && initialValues?.article == 'bike_rental'
                        }
                        options={
                            isEditing
                                ? articleOptionsWithRental
                                : articleOptions
                        }
                    />
                </Form.Item>

                <Form.Item name="purpose" label="Назначение платежа">
                    <Input.TextArea
                        rows={3}
                        placeholder="Например: Ремонт заднего колеса, замена спиц"
                    />
                </Form.Item>

                <Form.Item name="status" label="Статус">
                    <Select
                        disabled={!isEditing}
                        options={[
                            { value: 'unpaid', label: 'Не оплачен' },
                            { value: 'paid', label: 'Оплачен' },
                        ]}
                    />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default PaymentFormDrawer;

import { Button, DatePicker, Drawer, Form, Input, Select } from 'antd';
import dayjs, { Dayjs } from 'dayjs';
import React from 'react';

interface PaymentFormData {
    month: string;
    year: number;
    formation_date: Dayjs | null;
    payment_date: Dayjs | null;
    amount: number;
    payment_type: string;
    counterparty: string;
    category: string;
    purpose: string | null;
    status: 'paid' | 'unpaid';
}

interface PaymentFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: PaymentFormData) => void;
    initialValues?: Partial<PaymentFormData>;
    isEditing: boolean;
}

const PaymentFormDrawer: React.FC<PaymentFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    initialValues,
    isEditing,
}) => {
    const [form] = Form.useForm<PaymentFormData>();

    // Устанавливаем сегодняшнюю дату по умолчанию, если не редактируем
    const defaultValues = {
        formation_date: isEditing ? initialValues?.formation_date : dayjs(),
        payment_date: isEditing ? initialValues?.formation_date : dayjs(),
        ...initialValues,
    };

    return (
        <Drawer
            title={isEditing ? 'Редактировать платеж' : 'Создать платеж'}
            width={400}
            onClose={() => {
                onClose();
                form.resetFields();
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
                    <Button type="primary" onClick={() => form.submit()}>
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
                        formation_date: values.formation_date
                            ? values.formation_date
                            : null,
                        payment_date: values.payment_date
                            ? values.payment_date
                            : null,
                    });
                    form.resetFields();
                }}
            >
                <Form.Item
                    name="month"
                    label="Месяц"
                    rules={[{ required: true, message: 'Выберите месяц' }]}
                >
                    <Select
                        options={[
                            { value: 'Январь', label: 'Январь' },
                            { value: 'Февраль', label: 'Февраль' },
                            { value: 'Март', label: 'Март' },
                            { value: 'Апрель', label: 'Апрель' },
                            { value: 'Май', label: 'Май' },
                            { value: 'Июнь', label: 'Июнь' },
                            { value: 'Июль', label: 'Июль' },
                            { value: 'Август', label: 'Август' },
                            { value: 'Сентябрь', label: 'Сентябрь' },
                            { value: 'Октябрь', label: 'Октябрь' },
                            { value: 'Ноябрь', label: 'Ноябрь' },
                            { value: 'Декабрь', label: 'Декабрь' },
                        ]}
                    />
                </Form.Item>
                <Form.Item
                    name="year"
                    label="Год"
                    rules={[{ required: true, message: 'Введите год' }]}
                >
                    <Input type="number" />
                </Form.Item>
                <Form.Item
                    name="formation_date"
                    label="Дата формирования"
                    rules={[
                        {
                            required: true,
                            message: 'Выберите дату формирования',
                        },
                    ]}
                >
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item name="payment_date" label="Дата оплаты">
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item
                    name="amount"
                    label="Сумма (₽)"
                    rules={[{ required: true, message: 'Введите сумму' }]}
                >
                    <Input type="number" step="0.01" />
                </Form.Item>
                <Form.Item
                    name="payment_type"
                    label="Тип оплаты"
                    rules={[{ required: true, message: 'Выберите тип оплаты' }]}
                >
                    <Select
                        options={[
                            { value: 'card', label: 'Картой' },
                            { value: 'cash', label: 'Наличными' },
                            {
                                value: 'bank_transfer',
                                label: 'Банковский перевод',
                            },
                        ]}
                    />
                </Form.Item>
                <Form.Item
                    name="counterparty"
                    label="Контрагент"
                    rules={[
                        { required: true, message: 'Введите контрагента' },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="category"
                    label="Статья"
                    rules={[
                        { required: true, message: 'Введите статью' },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="purpose"
                    label="Назначение платежа"
                    rules={[{ max: 255, message: 'Максимум 255 символов' }]}
                >
                    <Input.TextArea rows={4} />
                </Form.Item>
                <Form.Item
                    name="status"
                    label="Статус"
                    rules={[{ required: true, message: 'Выберите статус' }]}
                >
                    <Select
                        options={[
                            { value: 'paid', label: 'Оплачено' },
                            { value: 'unpaid', label: 'Не оплачено' },
                        ]}
                    />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default PaymentFormDrawer;

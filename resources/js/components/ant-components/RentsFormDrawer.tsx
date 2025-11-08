import { Button, DatePicker, Drawer, Form, Input, Select } from 'antd';
import dayjs, { Dayjs } from 'dayjs';
import React from 'react';

interface RentFormData {
    client: string;
    bike: string;
    battery_1: string | null;
    battery_2: string | null;
    tariff: string;
    start_date: Dayjs | null;
    end_date: Dayjs | null;
    cost: number;
    paid: 'paid' | 'unpaid';
    note: string | null;
}

interface RentFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: RentFormData) => void;
    initialValues?: Partial<RentFormData>;
    isEditing: boolean;
}

const RentFormDrawer: React.FC<RentFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    initialValues,
    isEditing,
}) => {
    const [form] = Form.useForm<RentFormData>();

    // Устанавливаем сегодняшнюю дату по умолчанию для start_date, если не редактируем
    const defaultValues = {
        start_date: isEditing ? initialValues?.start_date : dayjs(),
        ...initialValues,
    };

    return (
        <Drawer
            title={isEditing ? 'Редактировать аренду' : 'Создать аренду'}
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
                    });
                    form.resetFields();
                }}
            >
                <Form.Item
                    name="client"
                    label="Клиент"
                    rules={[
                        { required: true, message: 'Введите клиента' },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="bike"
                    label="Велосипед"
                    rules={[
                        { required: true, message: 'Введите номер велосипеда' },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="battery_1"
                    label="АКБ №1"
                    rules={[{ max: 255, message: 'Максимум 255 символов' }]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="battery_2"
                    label="АКБ №2"
                    rules={[{ max: 255, message: 'Максимум 255 символов' }]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="tariff"
                    label="Тариф"
                    rules={[{ required: true, message: 'Выберите тариф' }]}
                >
                    <Select
                        options={[
                            { value: 'regular', label: 'Обычная' },
                            { value: 'scooter', label: 'Самокат' },
                            { value: 'cooper', label: 'Купер' },
                        ]}
                    />
                </Form.Item>
                <Form.Item
                    name="start_date"
                    label="Дата начала"
                    rules={[
                        { required: true, message: 'Выберите дату начала' },
                    ]}
                >
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item name="end_date" label="Дата окончания">
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item
                    name="cost"
                    label="Стоимость (₽)"
                    rules={[{ required: true, message: 'Введите стоимость' }]}
                >
                    <Input type="number" step="0.01" />
                </Form.Item>
                <Form.Item
                    name="paid"
                    label="Статус оплаты"
                    rules={[
                        { required: true, message: 'Выберите статус оплаты' },
                    ]}
                >
                    <Select
                        options={[
                            { value: 'paid', label: 'Оплачено' },
                            { value: 'unpaid', label: 'Не оплачено' },
                        ]}
                    />
                </Form.Item>
                <Form.Item
                    name="note"
                    label="Примечание"
                    rules={[{ max: 255, message: 'Максимум 255 символов' }]}
                >
                    <Input.TextArea rows={4} />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default RentFormDrawer;

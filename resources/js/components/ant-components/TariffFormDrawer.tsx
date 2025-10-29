import { Button, Drawer, Form, Input, Select } from 'antd';
import React from 'react';

interface TariffFormData {
    program: string;
    power: string;
    week_1: number;
    week_2: number;
    week_3: number;
    week_4: number;
    month_1: number;
}

interface TariffFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: TariffFormData) => void;
    initialValues?: Partial<TariffFormData>;
    isEditing: boolean;
}

const TariffFormDrawer: React.FC<TariffFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    initialValues,
    isEditing,
}) => {
    const [form] = Form.useForm<TariffFormData>();

    return (
        <Drawer
            title={isEditing ? 'Редактировать тариф' : 'Создать тариф'}
            width={400}
            onClose={onClose}
            open={visible}
            bodyStyle={{ paddingBottom: 80 }}
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
            <Form
                form={form}
                layout="vertical"
                initialValues={initialValues}
                onFinish={onSubmit}
            >
                <Form.Item
                    name="program"
                    label="Программа"
                    rules={[{ required: true, message: 'Выберите программу' }]}
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
                    name="power"
                    label="Мощность"
                    rules={[
                        { required: true, message: 'Введите мощность' },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="week_1"
                    label="1 неделя (₽)"
                    rules={[
                        {
                            required: true,
                            message: 'Введите стоимость за 1 неделю',
                        },
                        {
                            type: 'number',
                            min: 0,
                            message: 'Стоимость должна быть положительной',
                        },
                    ]}
                >
                    <Input type="number" step="0.01" />
                </Form.Item>
                <Form.Item
                    name="week_2"
                    label="2 неделя (₽)"
                    rules={[
                        {
                            required: true,
                            message: 'Введите стоимость за 2 неделю',
                        },
                        {
                            type: 'number',
                            min: 0,
                            message: 'Стоимость должна быть положительной',
                        },
                    ]}
                >
                    <Input type="number" step="0.01" />
                </Form.Item>
                <Form.Item
                    name="week_3"
                    label="3 неделя (₽)"
                    rules={[
                        {
                            required: true,
                            message: 'Введите стоимость за 3 неделю',
                        },
                        {
                            type: 'number',
                            min: 0,
                            message: 'Стоимость должна быть положительной',
                        },
                    ]}
                >
                    <Input type="number" step="0.01" />
                </Form.Item>
                <Form.Item
                    name="week_4"
                    label="4 неделя (₽)"
                    rules={[
                        {
                            required: true,
                            message: 'Введите стоимость за 4 неделю',
                        },
                        {
                            type: 'number',
                            min: 0,
                            message: 'Стоимость должна быть положительной',
                        },
                    ]}
                >
                    <Input type="number" step="0.01" />
                </Form.Item>
                <Form.Item
                    name="month_1"
                    label="1 месяц (₽)"
                    rules={[
                        {
                            required: true,
                            message: 'Введите стоимость за 1 месяц',
                        },
                        {
                            type: 'number',
                            min: 0,
                            message: 'Стоимость должна быть положительной',
                        },
                    ]}
                >
                    <Input type="number" step="0.01" />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default TariffFormDrawer;

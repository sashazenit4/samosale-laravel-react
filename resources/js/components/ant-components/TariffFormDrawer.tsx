import { Button, Drawer, Form, Input, InputNumber, Switch } from 'antd';
import React from 'react';

interface TariffFormData {
    program: string;
    power: number;
    price_month: number;
    price_week1: number;
    price_week2: number;
    price_week3: number;
    price_week4: number;
    is_active: boolean;
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
            width={480}
            onClose={onClose}
            open={visible}
            footer={
                <div style={{ textAlign: 'right', padding: '10px 0' }}>
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
                initialValues={{
                    is_active: true,
                    ...initialValues,
                }}
                onFinish={onSubmit}
            >
                <Form.Item
                    name="program"
                    label="Программа"
                    rules={[{ required: true, message: 'Выберите программу' }]}
                >
                    <Input />
                </Form.Item>

                <Form.Item
                    name="power"
                    label="Мощность (Вт)"
                    rules={[
                        { required: true, message: 'Укажите мощность' },
                        { type: 'number', min: 1, message: 'Мощность > 0' },
                    ]}
                >
                    <InputNumber style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item label="Цены по неделям">
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns: '1fr 1fr',
                            gap: 16,
                        }}
                    >
                        <Form.Item
                            name="price_week1"
                            label="1 неделя"
                            rules={[
                                { required: true },
                                { type: 'number', min: 0 },
                            ]}
                        >
                            <InputNumber
                                addonAfter="₽"
                                style={{ width: '100%' }}
                            />
                        </Form.Item>
                        <Form.Item
                            name="price_week2"
                            label="2 неделя"
                            rules={[
                                { required: true },
                                { type: 'number', min: 0 },
                            ]}
                        >
                            <InputNumber
                                addonAfter="₽"
                                style={{ width: '100%' }}
                            />
                        </Form.Item>
                        <Form.Item
                            name="price_week3"
                            label="3 неделя"
                            rules={[
                                { required: true },
                                { type: 'number', min: 0 },
                            ]}
                        >
                            <InputNumber
                                addonAfter="₽"
                                style={{ width: '100%' }}
                            />
                        </Form.Item>
                        <Form.Item
                            name="price_week4"
                            label="4 неделя"
                            rules={[
                                { required: true },
                                { type: 'number', min: 0 },
                            ]}
                        >
                            <InputNumber
                                addonAfter="₽"
                                style={{ width: '100%' }}
                            />
                        </Form.Item>
                    </div>
                </Form.Item>

                <Form.Item
                    name="price_month"
                    label="1 месяц (оптом)"
                    rules={[{ required: true }, { type: 'number', min: 0 }]}
                >
                    <InputNumber addonAfter="₽" style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item
                    name="is_active"
                    valuePropName="checked"
                    label="Активен"
                >
                    <Switch />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default TariffFormDrawer;

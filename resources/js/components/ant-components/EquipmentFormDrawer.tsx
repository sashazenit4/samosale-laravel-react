import { Button, Drawer, Form, Input, Select } from 'antd';
import React from 'react';

interface EquipmentFormData {
    number: string;
    status: string;
}

interface EquipmentFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: EquipmentFormData) => void;
    initialValues?: Partial<EquipmentFormData>;
    isEditing: boolean;
}

const EquipmentFormDrawer: React.FC<EquipmentFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    initialValues,
    isEditing,
}) => {
    const [form] = Form.useForm<EquipmentFormData>();

    return (
        <Drawer
            title={
                isEditing ? 'Редактировать аккумулятор' : 'Создать аккумулятор'
            }
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
                    name="number"
                    label="Номер"
                    rules={[
                        {
                            required: true,
                            message: 'Введите номер аккумулятора',
                        },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="status"
                    label="Статус"
                    rules={[{ required: true, message: 'Выберите статус' }]}
                >
                    <Select
                        options={[
                            { value: 'stolen', label: 'Угон' },
                            { value: 'free', label: 'Свободен' },
                            { value: 'rented', label: 'Аренда' },
                        ]}
                    />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default EquipmentFormDrawer;

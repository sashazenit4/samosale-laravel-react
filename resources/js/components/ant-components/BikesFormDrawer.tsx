import { Button, Drawer, Form, Input, Select } from 'antd';
import React from 'react';

interface BikeFormData {
    bike_number: string;
    frame_number: string;
    status: string;
    type: string;
}

interface BikeFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: BikeFormData) => void;
    initialValues?: Partial<BikeFormData>;
    isEditing: boolean;
}

const BikeFormDrawer: React.FC<BikeFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    initialValues,
    isEditing,
}) => {
    const [form] = Form.useForm<BikeFormData>();

    return (
        <Drawer
            title={isEditing ? 'Редактировать велосипед' : 'Создать велосипед'}
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
                    name="bike_number"
                    label="Номер велосипеда"
                    rules={[
                        { required: true, message: 'Введите номер велосипеда' },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="frame_number"
                    label="Номер рамы"
                    rules={[
                        { required: true, message: 'Введите номер рамы' },
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
                            { value: 'disassembly', label: 'Разбор' },
                            { value: 'stolen', label: 'Угон' },
                            { value: 'free', label: 'Свободен' },
                            { value: 'repair', label: 'Ремонт' },
                            { value: 'rented', label: 'Аренда' },
                            { value: 'reserved', label: 'Бронь' },
                        ]}
                    />
                </Form.Item>
                <Form.Item
                    name="type"
                    label="Тип"
                    rules={[{ required: true, message: 'Выберите тип' }]}
                >
                    <Select
                        options={[
                            { value: 'TRAK', label: 'ТРАК' },
                            { value: 'MOVER', label: 'МУВЕР' },
                        ]}
                    />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default BikeFormDrawer;

import { Button, Drawer, Form, Input, Select, Tag } from 'antd';
import React from 'react';

interface BikeFormData {
    bike_number: string;
    frame_number: string;
    status: string;
    type: 'TRAK' | 'MOVER';
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

    // Сброс формы при открытии
    React.useEffect(() => {
        if (visible) {
            form.resetFields();
            form.setFieldsValue(initialValues || {});
        }
    }, [visible, initialValues, form]);

    const statusOptions = [
        { value: 'disassembly', label: 'Разбор', color: 'orange' },
        { value: 'stolen', label: 'Угон', color: 'red' },
        { value: 'free', label: 'Свободен', color: 'green' },
        { value: 'repair', label: 'Ремонт', color: 'blue' },
        { value: 'renting', label: 'Аренда', color: 'purple' },
        { value: 'reserved', label: 'Бронь', color: 'cyan' },
    ];

    return (
        <Drawer
            title={isEditing ? 'Редактировать велосипед' : 'Создать велосипед'}
            width={420}
            onClose={onClose}
            open={visible}
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
            <Form form={form} layout="vertical" onFinish={onSubmit}>
                <Form.Item
                    name="bike_number"
                    label="Номер велосипеда"
                    rules={[{ required: true, message: 'Обязательно' }]}
                >
                    <Input placeholder="B-001" />
                </Form.Item>

                <Form.Item
                    name="frame_number"
                    label="Номер рамы"
                    rules={[{ required: true, message: 'Обязательно' }]}
                >
                    <Input placeholder="FR-123456" />
                </Form.Item>

                <Form.Item
                    name="status"
                    label="Статус"
                    rules={[{ required: true, message: 'Выберите статус' }]}
                >
                    <Select placeholder="Выберите статус">
                        {statusOptions.map(({ value, label, color }) => (
                            <Select.Option key={value} value={value}>
                                <Tag color={color}>{label}</Tag>
                            </Select.Option>
                        ))}
                    </Select>
                </Form.Item>

                <Form.Item
                    name="type"
                    label="Тип"
                    rules={[{ required: true, message: 'Выберите тип' }]}
                >
                    <Select placeholder="Выберите тип">
                        <Select.Option value="MotorRave">
                            <Tag color="green">МоторРейв</Tag>
                        </Select.Option>
                        <Select.Option value="MotorGlide">
                            <Tag color="orange">МоторГлайд</Tag>
                        </Select.Option>
                        <Select.Option value="MotorStream">
                            <Tag color="purple">МоторСтрим</Tag>
                        </Select.Option>
                        <Select.Option value="MotorFlow">
                            <Tag color="magenta">МоторФлоу</Tag>
                        </Select.Option>
                        <Select.Option value="MotorPulse">
                            <Tag color="blue">МоторПульс</Tag>
                        </Select.Option>
                    </Select>
                </Form.Item>
                <Form.Item name="property_1" label="Свойство 1">
                    <Input />
                </Form.Item>
                <Form.Item name="property_2" label="Свойство 2">
                    <Input />
                </Form.Item>
                <Form.Item name="property_3" label="Свойство 3">
                    <Input />
                </Form.Item>
                <Form.Item name="property_4" label="Свойство 4">
                    <Input />
                </Form.Item>
                <Form.Item name="property_5" label="Свойство 5">
                    <Input />
                </Form.Item>
                <Form.Item name="property_6" label="Свойство 6">
                    <Input />
                </Form.Item>
                <Form.Item name="property_7" label="Свойство 7">
                    <Input />
                </Form.Item>
                <Form.Item name="property_8" label="Свойство 8">
                    <Input />
                </Form.Item>
                <Form.Item name="property_9" label="Свойство 9">
                    <Input />
                </Form.Item>
                <Form.Item name="property_10" label="Свойство 10">
                    <Input />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default BikeFormDrawer;

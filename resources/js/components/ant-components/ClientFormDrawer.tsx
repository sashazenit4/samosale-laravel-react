import { Button, DatePicker, Drawer, Form, Input, Select } from 'antd';
import React from 'react';

export interface ClientFormData {
    contract_number: string;
    courier_id: string;
    last_name: string;
    first_name: string;
    middle_name: string | null;
    birth_date: Date | null;
    phone: string;
    additional_phone: string | null;
    relatives_phone: string | null;
    passport_series: string;
    passport_number: string;
    passport_issued_by: string;
    passport_issue_date: Date | null;
    passport_department_code: string;
    legal_address: string;
    actual_address: string;
    registration_date: Date | null;
    courier_service: string;
    attraction_source: string | null;
    service_start_date: Date | null;
    service_end_date: Date | null;
    serial_number: string | null;
    battery_1: string | null;
    battery_2: string | null;
}

interface ClientFormDrawerProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: ClientFormData) => void;
    initialValues?: Partial<ClientFormData>;
    isEditing: boolean;
}

const ClientFormDrawer: React.FC<ClientFormDrawerProps> = ({
    visible,
    onClose,
    onSubmit,
    initialValues,
    isEditing,
}) => {
    const [form] = Form.useForm<ClientFormData>();

    return (
        <Drawer
            title={isEditing ? 'Редактировать клиента' : 'Создать клиента'}
            width={600}
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
                initialValues={initialValues}
                onFinish={() => {
                    onSubmit(form.getFieldsValue());
                    form.resetFields();
                }}
            >
                <Form.Item
                    name="contract_number"
                    label="№ договора"
                    rules={[
                        { required: true, message: 'Введите номер договора' },
                        { max: 50, message: 'Максимум 50 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="courier_id"
                    label="ИД курьера"
                    rules={[
                        { required: true, message: 'Введите ИД курьера' },
                        { max: 20, message: 'Максимум 20 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="last_name"
                    label="Фамилия"
                    rules={[
                        { required: true, message: 'Введите фамилию' },
                        { max: 100, message: 'Максимум 100 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="first_name"
                    label="Имя"
                    rules={[
                        { required: true, message: 'Введите имя' },
                        { max: 100, message: 'Максимум 100 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="middle_name"
                    label="Отчество"
                    rules={[{ max: 100, message: 'Максимум 100 символов' }]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="birth_date"
                    label="Дата рождения"
                    rules={[
                        { type: 'object', message: 'Выберите дату' },
                        {
                            validator: (_, value) =>
                                value && value.isAfter(Date.now())
                                    ? Promise.reject(
                                          'Дата должна быть раньше текущей',
                                      )
                                    : Promise.resolve(),
                        },
                    ]}
                >
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item
                    name="additional_phone"
                    label="Доп. телефон"
                    rules={[{ max: 20, message: 'Максимум 20 символов' }]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="relatives_phone"
                    label="Телефон знакомых"
                    rules={[{ max: 20, message: 'Максимум 20 символов' }]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="passport_series"
                    label="Паспорт: серия"
                    rules={[
                        { required: true, message: 'Введите серию паспорта' },
                        { len: 4, message: 'Серия должна содержать 4 символа' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="passport_number"
                    label="Паспорт: номер"
                    rules={[
                        { required: true, message: 'Введите номер паспорта' },
                        {
                            len: 6,
                            message: 'Номер должен содержать 6 символов',
                        },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="passport_issued_by"
                    label="Кем выдан"
                    rules={[
                        {
                            required: true,
                            message: 'Введите кем выдан паспорт',
                        },
                        { max: 255, message: 'Максимум 255 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="passport_issue_date"
                    label="Когда выдан"
                    rules={[
                        { type: 'object', message: 'Выберите дату' },
                        {
                            validator: (_, value) =>
                                value && value.isAfter(Date.now())
                                    ? Promise.reject(
                                          'Дата должна быть раньше текущей',
                                      )
                                    : Promise.resolve(),
                        },
                    ]}
                >
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item
                    name="passport_department_code"
                    label="Код подразделения"
                    rules={[
                        {
                            required: true,
                            message: 'Введите код подразделения',
                        },
                        {
                            len: 7,
                            message:
                                'Код должен содержать 7 символов (XXX-XXX)',
                        },
                        {
                            pattern: /^\d{3}-\d{3}$/,
                            message: 'Формат: XXX-XXX',
                        },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="legal_address"
                    label="Адрес прописки"
                    rules={[
                        { required: true, message: 'Введите адрес прописки' },
                        { max: 500, message: 'Максимум 500 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="actual_address"
                    label="Адрес проживания"
                    rules={[
                        { required: true, message: 'Введите адрес проживания' },
                        { max: 500, message: 'Максимум 500 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="courier_service"
                    label="Курьерская служба"
                    rules={[
                        {
                            required: true,
                            message: 'Введите курьерскую службу',
                        },
                        { max: 100, message: 'Максимум 100 символов' },
                    ]}
                >
                    <Input />
                </Form.Item>
                <Form.Item
                    name="attraction_source"
                    label="Источник привлечения"
                    rules={[{ max: 50, message: 'Максимум 50 символов' }]}
                >
                    <Select
                        options={[
                            {
                                value: 'реклама_интернет',
                                label: 'Реклама в интернете',
                            },
                            {
                                value: 'реклама_улица',
                                label: 'Уличная реклама',
                            },
                            { value: 'рекомендация', label: 'Рекомендация' },
                            {
                                value: 'социальные_сети',
                                label: 'Социальные сети',
                            },
                            {
                                value: 'поисковые_системы',
                                label: 'Поисковые системы',
                            },
                            {
                                value: 'телефонный_звонок',
                                label: 'Телефонный звонок',
                            },
                            { value: 'другое', label: 'Другое' },
                        ]}
                        allowClear
                    />
                </Form.Item>
                <Form.Item
                    name="service_start_date"
                    label="Начало пользования"
                    rules={[{ type: 'object', message: 'Выберите дату' }]}
                >
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item
                    name="service_end_date"
                    label="Конец пользования"
                    rules={[
                        { type: 'object', message: 'Выберите дату' },
                        {
                            validator: (_, value) =>
                                value &&
                                form.getFieldValue('service_start_date') &&
                                value.isBefore(
                                    form.getFieldValue('service_start_date'),
                                )
                                    ? Promise.reject(
                                          'Дата окончания должна быть позже даты начала',
                                      )
                                    : Promise.resolve(),
                        },
                    ]}
                >
                    <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
                </Form.Item>
            </Form>
        </Drawer>
    );
};

export default ClientFormDrawer;

import PaymentFormDrawer from '@/components/ant-components/PaymentsFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { Head } from '@inertiajs/react';
import { Button, ConfigProvider, Input, Space, Table } from 'antd';
import { CoinsIcon, FilterIcon } from 'lucide-react';
import { useState } from 'react';
import { paymentsColumns } from './columnsConfig';

interface PaymentFormData {
    month: string;
    year: number;
    formation_date: string | null;
    payment_date: string | null;
    amount: number;
    payment_type: string;
    counterparty: string;
    category: string;
    purpose: string;
    status: 'paid' | 'unpaid';
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Платежи',
        href: dashboard().url,
    },
];

const mockPayments = [
    {
        id: 1,
        month: 'Январь',
        year: 2025,
        formation_date: '2025-01-01',
        payment_date: '2025-01-05',
        amount: 5000,
        payment_type: 'card',
        counterparty: 'Иванов Иван Иванович',
        category: 'Аренда велосипеда',
        purpose: 'Оплата аренды за январь',
        status: 'paid',
    },
    {
        id: 2,
        month: 'Февраль',
        year: 2025,
        formation_date: '2025-02-01',
        payment_date: null,
        amount: 4500,
        payment_type: 'cash',
        counterparty: 'Петров Пётр Петрович',
        category: 'Ремонт оборудования',
        purpose: 'Оплата ремонта аккумулятора',
        status: 'unpaid',
    },
];

export default function Payments() {
    const [search, setSearch] = useState<string>('');
    const [drawerVisible, setDrawerVisible] = useState<boolean>(false);
    const [editingPayment, setEditingPayment] = useState<
        (typeof mockPayments)[0] | null
    >(null);

    const filteredPayments = mockPayments.filter(
        (payment) =>
            payment.counterparty.toLowerCase().includes(search.toLowerCase()) ||
            payment.purpose.toLowerCase().includes(search.toLowerCase()),
    );

    const openDrawer = (payment?: (typeof mockPayments)[0]) => {
        setEditingPayment(payment || null);
        setDrawerVisible(true);
    };

    const closeDrawer = () => {
        setDrawerVisible(false);
        setEditingPayment(null);
    };

    const onSubmit = (values: PaymentFormData) => {
        console.log('Form values:', {
            ...values,
            formation_date: values.formation_date,
            payment_date: values.payment_date,
        });
        // Здесь добавь логику для отправки на сервер (Inertia.post/put)
        closeDrawer();
    };

    const columns = paymentsColumns(openDrawer);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Платежи" />
            <ConfigProvider
                theme={{
                    token: {
                        colorPrimary: 'oklch(0.205 0 0)', // --primary
                        borderRadius: 6, // --radius (0.625rem ≈ 6px)
                        fontFamily:
                            "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
                        colorBgContainer: 'oklch(1 0 0)', // --background
                        colorText: 'oklch(0.145 0 0)', // --foreground
                        colorBorder: 'oklch(0.922 0 0)', // --border
                    },
                    components: {
                        Table: {
                            headerBg: 'oklch(0.97 0 0)', // --secondary
                            headerColor: 'oklch(0.145 0 0)', // --foreground
                            rowHoverBg: 'oklch(0.97 0 0)', // --muted
                        },
                        Input: {
                            activeBorderColor: 'oklch(0.205 0 0)', // --primary
                            hoverBorderColor: 'oklch(0.87 0 0)', // --ring
                        },
                        Tag: {
                            defaultBg: 'oklch(0.97 0 0)', // --secondary
                            defaultColor: 'oklch(0.145 0 0)', // --foreground
                        },
                    },
                }}
            >
                <div style={{ padding: '24px' }}>
                    <Space
                        style={{
                            width: '100%',
                            marginBottom: '16px',
                            justifyContent: 'space-between',
                        }}
                        size="large"
                    >
                        <Space
                            style={{
                                width: '100%',
                            }}
                            size="large"
                        >
                            <Button
                                type="primary"
                                icon={<PlusOutlined />}
                                onClick={() => openDrawer()}
                            >
                                Добавить платеж
                            </Button>

                            <Button
                                type="default"
                                icon={<CoinsIcon size="20px" />}
                                onClick={() => openDrawer()}
                            >
                                Выслать уведомления в бот
                            </Button>
                        </Space>
                        <Space
                            style={{
                                width: '100%',
                            }}
                            size="large"
                        >
                            <Input
                                placeholder="Поиск по контрагенту или назначению платежа"
                                prefix={<SearchOutlined />}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                allowClear
                            />
                            <Button icon={<FilterIcon size="18px" />} />
                        </Space>
                    </Space>
                    <Table
                        columns={columns}
                        dataSource={filteredPayments}
                        rowKey="id"
                        pagination={{ pageSize: 10 }}
                        scroll={{ x: 'max-content' }}
                        locale={{ emptyText: 'Нет данных для отображения' }}
                    />

                    <PaymentFormDrawer
                        visible={drawerVisible}
                        onClose={closeDrawer}
                        onSubmit={onSubmit}
                        initialValues={
                            editingPayment
                                ? {
                                      ...editingPayment,
                                      formation_date:
                                          editingPayment.formation_date
                                              ? new Date(
                                                    editingPayment.formation_date,
                                                )
                                              : null,
                                      payment_date: editingPayment.payment_date
                                          ? new Date(
                                                editingPayment.payment_date,
                                            )
                                          : null,
                                  }
                                : undefined
                        }
                        isEditing={!!editingPayment}
                    />
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

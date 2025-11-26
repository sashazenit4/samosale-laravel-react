import PaymentFormDrawer from '@/components/ant-components/PaymentsFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { Inertia } from '@inertiajs/inertia';
import { Head, router, usePage } from '@inertiajs/react';
import { Button, ConfigProvider, Input, Space, Table, message } from 'antd';
import { CoinsIcon } from 'lucide-react';
import { useState } from 'react';
import { paymentsColumns } from './columnsConfig';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Платежи', href: dashboard().url },
];

export default function Payments() {
    const { payments, filters, clients_options } = usePage().props as any;
    const [search, setSearch] = useState(filters?.search || '');
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [editingPayment, setEditingPayment] = useState<any>(null);

    const openDrawer = (payment?: any) => {
        setEditingPayment(payment || null);
        setDrawerVisible(true);
    };

    const closeDrawer = () => {
        setDrawerVisible(false);
        setEditingPayment(null);
    };

    const onSubmit = (values: any) => {
        console.log(values);
        const url = editingPayment
            ? `/payments/${editingPayment.id}`
            : '/payments';
        if (editingPayment) {
            Inertia.put(url, values, {
                onSuccess: () => {
                    message.success(
                        editingPayment ? 'Платёж обновлён' : 'Платёж создан',
                    );
                    closeDrawer();
                },
            });
        } else {
            Inertia.post(url, values, {
                onSuccess: () => {
                    message.success(
                        editingPayment ? 'Платёж обновлён' : 'Платёж создан',
                    );
                    closeDrawer();
                },
            });
        }
    };

    const handleDelete = (id: number) => {
        router.delete(`/payments/${id}`, {
            onSuccess: () => message.success('Платёж удалён'),
        });
    };

    const handleSearch = (value: string) => {
        router.get(
            '/payments',
            { search: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Платежи" />

            <ConfigProvider
                theme={
                    {
                        /* твоя тема */
                    }
                }
            >
                <div style={{ padding: '24px' }}>
                    <Space
                        style={{
                            marginBottom: 16,
                            justifyContent: 'space-between',
                            width: '100%',
                        }}
                    >
                        <Space>
                            <Button
                                type="primary"
                                icon={<PlusOutlined />}
                                onClick={() => openDrawer()}
                            >
                                Добавить платёж
                            </Button>
                            <Button icon={<CoinsIcon size={20} />}>
                                Выслать уведомления
                            </Button>
                        </Space>

                        <Input
                            placeholder="Поиск..."
                            prefix={<SearchOutlined />}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onPressEnter={(e) =>
                                handleSearch(e.currentTarget.value)
                            }
                            allowClear
                            style={{ width: 320 }}
                        />
                    </Space>

                    <Table
                        columns={paymentsColumns(openDrawer, handleDelete)}
                        dataSource={payments?.data || []}
                        rowKey="id"
                        scroll={{ x: 1400 }}
                        pagination={{
                            current: payments.meta?.current_page,
                            pageSize: payments.meta?.per_page,
                            total: payments.meta?.total,
                        }}
                        onChange={(pagination) => {
                            router.get(
                                '/payments',
                                {
                                    page: pagination.current,
                                    search,
                                },
                                { preserveState: true },
                            );
                        }}
                    />

                    <PaymentFormDrawer
                        visible={drawerVisible}
                        onClose={closeDrawer}
                        onSubmit={onSubmit}
                        initialValues={editingPayment}
                        isEditing={!!editingPayment}
                        clients={clients_options}
                    />
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

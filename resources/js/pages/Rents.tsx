import RentFormDrawer from '@/components/ant-components/RentsFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { Head } from '@inertiajs/react';
import { Button, ConfigProvider, Input, Space, Table } from 'antd';
import { FilterIcon } from 'lucide-react';
import { useState } from 'react';
import { rentsColumns } from './columnsConfig';

interface RentFormData {
    id: number;
    client: string;
    bike: string;
    battery_1: string | null;
    battery_2: string | null;
    tariff: string;
    start_date: string | null;
    end_date: string | null;
    cost: number;
    paid: 'paid' | 'unpaid';
    note: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Аренда',
        href: dashboard().url,
    },
];

const mockRents = [
    {
        id: 1,
        client: 'Иванов Иван Иванович',
        bike: 'BIKE001',
        battery_1: 'BAT001',
        battery_2: 'BAT002',
        tariff: 'Обычная',
        start_date: '2025-01-01',
        end_date: '2025-01-31',
        cost: 3200,
        paid: 'paid',
        note: 'Аренда на месяц',
    },
    {
        id: 2,
        client: 'Петров Пётр Петрович',
        bike: 'BIKE002',
        battery_1: null,
        battery_2: null,
        tariff: 'Самокат',
        start_date: '2025-02-01',
        end_date: null,
        cost: 1200,
        paid: 'unpaid',
        note: 'Неделя аренды',
    },
];

export default function Rents() {
    const [search, setSearch] = useState<string>('');
    const [drawerVisible, setDrawerVisible] = useState<boolean>(false);
    const [editingRent, setEditingRent] = useState<
        (typeof mockRents)[0] | null
    >(null);

    const filteredRents = mockRents.filter(
        (rent) =>
            rent.client.toLowerCase().includes(search.toLowerCase()) ||
            (rent.note &&
                rent.note.toLowerCase().includes(search.toLowerCase())),
    );

    const openDrawer = (rent?: (typeof mockRents)[0]) => {
        setEditingRent(rent || null);
        setDrawerVisible(true);
    };

    const closeDrawer = () => {
        setDrawerVisible(false);
        setEditingRent(null);
    };

    const onSubmit = (values: RentFormData) => {
        console.log('Form values:', {
            ...values,
            start_date: values.start_date,
            end_date: values.end_date,
        });
        // Здесь добавь логику для отправки на сервер (Inertia.post/put)
        closeDrawer();
    };

    const columns = rentsColumns(openDrawer);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Аренда" />
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
                        <Button
                            type="primary"
                            icon={<PlusOutlined />}
                            onClick={() => openDrawer()}
                        >
                            Добавить аренду
                        </Button>
                        <Space
                            style={{
                                width: '100%',
                            }}
                            size="large"
                        >
                            <Input
                                placeholder="Поиск по клиенту или примечанию"
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
                        dataSource={filteredRents}
                        rowKey="id"
                        pagination={{ pageSize: 10 }}
                        scroll={{ x: 'max-content' }}
                        locale={{ emptyText: 'Нет данных для отображения' }}
                    />
                    <RentFormDrawer
                        visible={drawerVisible}
                        onClose={closeDrawer}
                        onSubmit={onSubmit}
                        initialValues={
                            editingRent
                                ? {
                                      ...editingRent,
                                      start_date: editingRent.start_date
                                          ? new Date(editingRent.start_date)
                                          : null,
                                      end_date: editingRent.end_date
                                          ? new Date(editingRent.end_date)
                                          : null,
                                  }
                                : undefined
                        }
                        isEditing={!!editingRent}
                    />
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

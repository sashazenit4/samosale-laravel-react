import ClientFormDrawer, {
    ClientFormData,
} from '@/components/ant-components/ClientFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { Head } from '@inertiajs/react';
import { Button, ConfigProvider, Input, Space, Table } from 'antd';
import { FilterIcon } from 'lucide-react';
import { useState } from 'react';
import { clientsColumns } from './columnsConfig';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Клиенты',
        href: dashboard().url,
    },
];

const mockClients = [
    {
        id: 1,
        contract_number: 'C001',
        courier_id: 'CR001',
        last_name: 'Иванов',
        first_name: 'Иван',
        middle_name: 'Иванович',
        birth_date: '1990-01-01',
        phone: '+79991234567',
        additional_phone: '+79997654321',
        relatives_phone: '+79999876543',
        passport_series: '1234',
        passport_number: '567890',
        passport_issued_by: 'ОВД Москвы',
        passport_issue_date: '2010-01-01',
        passport_department_code: '123-456',
        legal_address: 'Москва, ул. Ленина, д. 1',
        actual_address: 'Москва, ул. Мира, д. 2',
        registration_date: '2023-01-01',
        courier_service: 'Яндекс.Доставка',
        attraction_source: 'реклама_интернет',
        service_start_date: '2023-01-01',
        service_end_date: null,
        serial_number: 'SN12345',
        battery_1: 'BAT001',
        battery_2: 'BAT002',
    },
    {
        id: 2,
        contract_number: 'C002',
        courier_id: 'CR002',
        last_name: 'Петров',
        first_name: 'Пётр',
        middle_name: 'Петрович',
        birth_date: '1992-02-02',
        phone: '+79991234568',
        additional_phone: null,
        relatives_phone: null,
        passport_series: '5678',
        passport_number: '123456',
        passport_issued_by: 'ОВД Санкт-Петербурга',
        passport_issue_date: '2012-02-02',
        passport_department_code: '789-012',
        legal_address: 'Санкт-Петербург, ул. Невская, д. 3',
        actual_address: 'Санкт-Петербург, ул. Морская, д. 4',
        registration_date: '2023-02-01',
        courier_service: 'Достависта',
        attraction_source: 'социальные_сети',
        service_start_date: '2023-02-01',
        service_end_date: null,
        serial_number: 'SN67890',
        battery_1: null,
        battery_2: null,
    },
];

export default function Dashboard() {
    const [search, setSearch] = useState<string>('');
    const [drawerVisible, setDrawerVisible] = useState<boolean>(false);
    const [editingClient, setEditingClient] = useState<
        (typeof mockClients)[0] | null
    >(null);

    const filteredClients = mockClients.filter(
        (client) =>
            client.last_name.toLowerCase().includes(search.toLowerCase()) ||
            client.first_name.toLowerCase().includes(search.toLowerCase()) ||
            (client.middle_name &&
                client.middle_name
                    .toLowerCase()
                    .includes(search.toLowerCase())) ||
            client.contract_number.toLowerCase().includes(search.toLowerCase()),
    );

    const openDrawer = (client?: any) => {
        setEditingClient(client || null);
        setDrawerVisible(true);
    };

    const closeDrawer = () => {
        setDrawerVisible(false);
        setEditingClient(null);
    };

    const onSubmit = (values: ClientFormData) => {
        console.log('Form values:', {
            ...values,
            // birth_date: values.birth_date
            //     ? values.birth_date.format('YYYY-MM-DD')
            //     : null,
            // passport_issue_date: values.passport_issue_date
            //     ? values.passport_issue_date.format('YYYY-MM-DD')
            //     : null,
            // registration_date: values.registration_date
            //     ? values.registration_date.format('YYYY-MM-DD')
            //     : null,
            // service_start_date: values.service_start_date
            //     ? values.service_start_date.format('YYYY-MM-DD')
            //     : null,
            // service_end_date: values.service_end_date
            //     ? values.service_end_date.format('YYYY-MM-DD')
            //     : null,
        });
        // Здесь добавь логику для отправки на сервер (Inertia.post/put)
        closeDrawer();
    };

    const columns = clientsColumns(openDrawer);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Клиенты" />
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
                            Создать клиента
                        </Button>
                        <Space
                            style={{
                                width: '100%',
                            }}
                            size="large"
                        >
                            <Input
                                placeholder="Поиск по фамилии, имени, отчеству, номеру договора"
                                prefix={<SearchOutlined />}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                allowClear
                            />
                            <Button icon={<FilterIcon size="18px" />}></Button>
                        </Space>
                    </Space>
                    <Table
                        columns={columns}
                        dataSource={filteredClients}
                        rowKey="id"
                        pagination={{ pageSize: 10 }}
                        scroll={{ x: 'max-content' }}
                        locale={{ emptyText: 'Нет данных для отображения' }}
                    />
                    <ClientFormDrawer
                        visible={drawerVisible}
                        onClose={closeDrawer}
                        onSubmit={onSubmit}
                        initialValues={
                            editingClient
                                ? {
                                      ...editingClient,
                                      birth_date: editingClient.birth_date
                                          ? new Date(editingClient.birth_date)
                                          : null,
                                      passport_issue_date:
                                          editingClient.passport_issue_date
                                              ? new Date(
                                                    editingClient.passport_issue_date,
                                                )
                                              : null,
                                      registration_date:
                                          editingClient.registration_date
                                              ? new Date(
                                                    editingClient.registration_date,
                                                )
                                              : null,
                                      service_start_date:
                                          editingClient.service_start_date
                                              ? new Date(
                                                    editingClient.service_start_date,
                                                )
                                              : null,
                                      service_end_date:
                                          editingClient.service_end_date
                                              ? new Date(
                                                    editingClient.service_end_date,
                                                )
                                              : null,
                                  }
                                : undefined
                        }
                        isEditing={!!editingClient}
                    />
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

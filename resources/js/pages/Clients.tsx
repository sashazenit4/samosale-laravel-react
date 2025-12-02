import ClientFormDrawer from '@/components/ant-components/ClientFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { SearchOutlined } from '@ant-design/icons';
import { Inertia } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/react';
import {
    Button,
    ConfigProvider,
    Input,
    message,
    Modal,
    Space,
    Table,
} from 'antd';
import ruRU from 'antd/locale/ru_RU';
import dayjs from 'dayjs';
import 'dayjs/locale/ru';
import utc from 'dayjs/plugin/utc';
import { FilterIcon } from 'lucide-react';
import { useState } from 'react';
import { clientsColumns } from './columnsConfig';
dayjs.locale('ru');

dayjs.extend(utc);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Клиенты', href: dashboard().url },
];

export default function Dashboard() {
    const [drawerVisible, setDrawerVisible] = useState<boolean>(false);
    const [editingClient, setEditingClient] = useState<any | null>(null);
    const [deleteModal, setDeleteModal] = useState<{
        visible: boolean;
        client: any | null;
    }>({
        visible: false,
        client: null,
    });

    const { clients, filters } = usePage().props as any;

    const [search, setSearch] = useState<string>(filters.search || '');

    // Обработчик смены страницы
    const handleTableChange = (pagination: any) => {
        Inertia.get(
            '/clients',
            { search, page: pagination.current },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleSearch = () => {
        Inertia.get('/clients', { search }, { preserveState: true });
    };

    const openDrawer = (client?: any) => {
        setEditingClient(prepareClient(client) || null);
        setDrawerVisible(true);
    };

    const closeDrawer = () => {
        setDrawerVisible(false);
        setEditingClient(null);
    };

    const openConfirmDelete = (client: any) => {
        setDeleteModal({ visible: true, client });
    };

    const closeConfirmDelete = () => {
        setDeleteModal({ visible: false, client: null });
    };

    const handleDelete = () => {
        if (!deleteModal.client) return;

        Inertia.delete(`/clients/${deleteModal.client.user_id}`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                message.success('Клиент удалён');
                closeConfirmDelete();
                Inertia.reload({ only: ['clients'] });
            },
            onError: () => {
                message.error('Ошибка при удалении');
            },
        });
    };

    const onSubmit = (values: any) => {
        if (!editingClient) return;

        const payload: any = {
            custom_fields: Object.keys(values)
                .map((key: string) => {
                    if (values[key]) {
                        return {
                            name: key,
                            value: values[key],
                        };
                    }
                })
                .filter((item) => !!item && item.name !== 'user_id'),
        };

        console.log(payload);

        Inertia.put(`/clients/${editingClient.user_id}`, payload, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                message.success('Обновлено');
                setDrawerVisible(false);
            },
            onError: (errors) => {
                console.log('Ошибки:', errors);
            },
        });
        closeDrawer();
    };

    function prepareClient(editingClient: any) {
        const data = Object({ user_id: editingClient.user_id });
        const dates = [
            'birth_date',
            'passport_issue_date',
            'service_start_date',
            'service_end_date',
            'issue_date',
        ];
        (editingClient.custom_fields || []).forEach((item: any) => {
            if (dates.includes(item?.field_name)) {
                data[item?.field_name] = dayjs(item.field_value);
            } else {
                data[item?.field_name] = item.field_value;
            }
        });
        return data;
    }

    const columns = clientsColumns(openDrawer, openConfirmDelete);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Клиенты" />
            <ConfigProvider
                locale={ruRU}
                theme={{
                    token: {
                        colorPrimary: 'oklch(0.205 0 0)',
                        borderRadius: 6,
                        fontFamily:
                            "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
                        colorBgContainer: 'oklch(1 0 0)',
                        colorText: 'oklch(0.145 0 0)',
                        colorBorder: 'oklch(0.922 0 0)',
                    },
                    components: {
                        Table: {
                            headerBg: 'oklch(0.97 0 0)',
                            headerColor: 'oklch(0.145 0 0)',
                            rowHoverBg: 'oklch(0.97 0 0)',
                        },
                        Input: {
                            activeBorderColor: 'oklch(0.205 0 0)',
                            hoverBorderColor: 'oklch(0.87 0 0)',
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
                        <div></div>
                        <Space style={{ width: '100%' }} size="large">
                            <Input
                                placeholder="Поиск по ФИО, договору..."
                                prefix={<SearchOutlined />}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onPressEnter={handleSearch}
                                allowClear
                                style={{ width: 300 }}
                            />
                            <Button icon={<FilterIcon size="18px" />}></Button>
                        </Space>
                    </Space>

                    <Table
                        columns={columns}
                        dataSource={clients.data || []}
                        rowKey="user_id"
                        pagination={{
                            current: clients.current_page,
                            pageSize: clients.per_page,
                            total: clients.total,
                            showSizeChanger: false,
                        }}
                        onChange={handleTableChange}
                        scroll={{ x: 'max-content' }}
                        locale={{ emptyText: 'Нет данных для отображения' }}
                        bordered={true}
                    />

                    <ClientFormDrawer
                        key={editingClient?.user_id}
                        visible={drawerVisible}
                        onClose={closeDrawer}
                        onSubmit={onSubmit}
                        initialValues={editingClient}
                        isEditing={!!editingClient}
                    />

                    {/* Модалка подтверждения удаления */}
                    <Modal
                        title="Удалить клиента?"
                        open={deleteModal.visible}
                        onCancel={closeConfirmDelete}
                        footer={[
                            <Button key="cancel" onClick={closeConfirmDelete}>
                                Отмена
                            </Button>,
                            <Button
                                key="delete"
                                type="primary"
                                danger
                                onClick={handleDelete}
                            >
                                Удалить
                            </Button>,
                        ]}
                    >
                        <p>
                            Вы уверены, что хотите удалить клиента{' '}
                            <strong>
                                {(deleteModal.client?.custom_fields || []).find(
                                    (f: any) => f.field_name === 'last_name',
                                )?.field_value || ''}{' '}
                                {(deleteModal.client?.custom_fields || []).find(
                                    (f: any) => f.field_name === 'first_name',
                                )?.field_value || ''}
                            </strong>
                            ?
                        </p>
                        <p>Это действие нельзя отменить.</p>
                    </Modal>
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

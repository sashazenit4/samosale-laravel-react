import RentFormDrawer from '@/components/ant-components/RentsFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { Inertia, PageProps as InertiaPageProps } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/react';
import {
    Button,
    ConfigProvider,
    Form,
    Input,
    message,
    Modal,
    Select,
    Space,
    Table,
} from 'antd';
import dayjs from 'dayjs';
import { FilterIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { rentsColumns } from './columnsConfig';
import { mockRents } from './mockData';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Аренда', href: dashboard().url },
];

interface Tariff {
    id: number;
    program: string;
}

interface Rent {
    id: number;
    client: string;
    bike: string;
    battery_capacity: number | null;
    battery_count: number;
    tariff: Tariff;
    tariff_type: '1 week' | 'next weeks' | 'month';
    tariff_price: number;
    start_date: string;
    end_date: string | null;
    cost: number;
    paid: 'paid' | 'unpaid';
    is_completed: boolean;
    note: string | null;
}

interface PageProps extends InertiaPageProps {
    rents: any;
    filters: any;
    clients_options: { id: number; name: string }[];
    bikes_options: { id: number; number: string }[];
    tariffs_options: {
        id: number;
        program: string;
        price_week1: number;
        price_week2: number;
        price_month: number;
    }[];
}

export default function Rents() {
    const { rents, filters, clients_options, bikes_options, tariffs_options } =
        usePage<PageProps>().props;

    console.log(
        rents,
        filters,
        clients_options,
        bikes_options,
        tariffs_options,
    );

    const [search, setSearch] = useState<string>(filters.search || '');
    const [drawer, setDrawer] = useState<{
        visible: boolean;
        record: Rent | null;
    }>({
        visible: false,
        record: null,
    });
    const [deleteModal, setDeleteModal] = useState<{
        visible: boolean;
        record: Rent | null;
    }>({
        visible: false,
        record: null,
    });

    const [extendModal, setExtendModal] = useState<{
        visible: boolean;
        record: Rent | null;
        weeks: number;
    }>({
        visible: false,
        record: null,
        weeks: 1,
    });

    useEffect(() => {
        if (filters.search !== search) setSearch(filters.search || '');
    }, [filters.search]);

    const go = (params: any = {}) => {
        Inertia.get(
            '/rents',
            { ...params, search },
            { preserveState: true, replace: true },
        );
    };

    const handleSearch = () => go({ search });
    const handleTableChange = (pagination: any) =>
        go({ page: pagination.current });

    const openDrawer = (record?: Rent) => {
        setDrawer({ visible: true, record: record || null });
    };

    const closeDrawer = () => {
        setDrawer({ visible: false, record: null });
    };

    const openDeleteModal = (record: Rent) => {
        setDeleteModal({ visible: true, record });
    };

    const handleDelete = () => {
        if (!deleteModal.record) return;
        Inertia.delete(`/rents/${deleteModal.record.id}`, {
            preserveState: true,
            onSuccess: () => {
                message.success('Аренда удалена');
                setDeleteModal({ visible: false, record: null });
            },
            onError: () => message.error('Ошибка удаления'),
        });
    };

    // ВАЖНО: Используем Modal.confirm ИЗ ConfigProvider
    const openExtendModal = (record: Rent) => {
        setExtendModal({ visible: true, record, weeks: 1 });
    };

    const closeExtendModal = () => {
        setExtendModal({ visible: false, record: null, weeks: 1 });
    };

    const onSubmit = (values: any) => {
        console.log(values);
        // const isEdit = !!drawer.record;
        // const url = isEdit ? `/rents/${drawer.record!.id}` : '/rents';
        // const method = isEdit ? Inertia.put : Inertia.post;

        // method(url, values, {
        //     preserveState: true,
        //     preserveScroll: true,
        //     onSuccess: () => {
        //         message.success(isEdit ? 'Аренда обновлена' : 'Аренда создана');
        //         closeDrawer();
        //     },
        //     onError: () => message.error('Проверьте поля'),
        // });
    };

    const columns = rentsColumns(openDrawer, openExtendModal, openDeleteModal);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Аренда" />
            {/* ВСЁ ВНУТРИ ConfigProvider */}
            <ConfigProvider
                theme={{
                    token: {
                        colorPrimary: 'oklch(0.205 0 0)',
                        borderRadius: 6,
                    },
                    components: {
                        Table: {
                            headerBg: 'oklch(0.97 0 0)',
                            rowHoverBg: 'oklch(0.97 0 0)',
                            rowSelectedBg: '#ededed',
                            rowSelectedHoverBg: '#ededed',
                        },
                        Modal: {
                            borderRadiusLG: 8,
                        },
                    },
                }}
                getPopupContainer={() => document.body}
            >
                <div style={{ padding: '24px' }}>
                    <Space
                        style={{
                            width: '100%',
                            marginBottom: 16,
                            justifyContent: 'space-between',
                        }}
                    >
                        <Button
                            type="primary"
                            icon={<PlusOutlined />}
                            onClick={() => openDrawer()}
                        >
                            Добавить аренду
                        </Button>
                        <Space>
                            <Input
                                placeholder="Поиск по клиенту, примечанию..."
                                prefix={<SearchOutlined />}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onPressEnter={handleSearch}
                                allowClear
                                style={{ width: 320 }}
                            />
                            <Button icon={<FilterIcon size={18} />} />
                        </Space>
                    </Space>

                    <Table
                        columns={columns}
                        dataSource={rents?.data || mockRents}
                        rowKey="id"
                        pagination={{
                            current: rents?.current_page || 1,
                            pageSize: rents?.per_page || 10,
                            total: rents?.total || 0,
                            showQuickJumper: true,
                        }}
                        onChange={handleTableChange}
                        scroll={{ x: 'max-content' }}
                        locale={{ emptyText: 'Нет аренд' }}
                    />

                    <RentFormDrawer
                        visible={drawer.visible}
                        onClose={closeDrawer}
                        onSubmit={onSubmit}
                        clients={clients_options}
                        bikes={bikes_options}
                        tariffs={tariffs_options}
                        initialValues={drawer.record}
                        isEditing={!!drawer.record}
                    />

                    <Modal
                        title="Удалить аренду?"
                        open={deleteModal.visible}
                        onCancel={() =>
                            setDeleteModal({ visible: false, record: null })
                        }
                        onOk={handleDelete}
                        okText="Удалить"
                        cancelText="Отмена"
                        okButtonProps={{ danger: true }}
                    >
                        <p>Вы уверены? Это действие нельзя отменить.</p>
                    </Modal>
                    <Modal
                        title="Продлить аренду"
                        open={extendModal.visible}
                        onCancel={closeExtendModal}
                        onOk={() => {
                            if (!extendModal.record) return;

                            const days =
                                extendModal.weeks === 4
                                    ? 30
                                    : extendModal.weeks * 7;

                            Inertia.put(
                                `/rents/${extendModal.record.id}/extend`,
                                { days },
                                {
                                    preserveState: true,
                                    onSuccess: () => {
                                        const label =
                                            extendModal.weeks === 4
                                                ? '1 месяц'
                                                : `${extendModal.weeks} нед.`;
                                        message.success(
                                            `Аренда продлена на ${label}`,
                                        );
                                        closeExtendModal();
                                    },
                                    onError: () =>
                                        message.error('Ошибка продления'),
                                },
                            );
                        }}
                        okText="Продлить"
                        cancelText="Отмена"
                    >
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <div>
                                <strong>Клиент:</strong>{' '}
                                {extendModal.record?.client}
                            </div>
                            <div>
                                <strong>Велосипед:</strong>{' '}
                                {extendModal.record?.bike}
                            </div>
                            <div>
                                <strong>Текущая дата окончания:</strong>{' '}
                                {extendModal.record?.end_date
                                    ? dayjs
                                          .utc(extendModal.record.end_date)
                                          .format('DD.MM.YYYY')
                                    : 'не установлена'}
                            </div>

                            <Form.Item
                                label="На сколько продлить?"
                                style={{ marginBottom: 0 }}
                            >
                                <Select
                                    value={extendModal.weeks}
                                    onChange={(value) =>
                                        setExtendModal((prev) => ({
                                            ...prev,
                                            weeks: value,
                                        }))
                                    }
                                    style={{ width: '100%' }}
                                >
                                    <Select.Option value={1}>
                                        1 неделя
                                    </Select.Option>
                                    <Select.Option value={2}>
                                        2 недели
                                    </Select.Option>
                                    <Select.Option value={3}>
                                        3 недели
                                    </Select.Option>
                                    <Select.Option value={4}>
                                        1 месяц
                                    </Select.Option>
                                </Select>
                            </Form.Item>

                            <div style={{ color: '#888', fontSize: '0.9em' }}>
                                {extendModal.weeks === 4
                                    ? '→ +30 дней'
                                    : `→ +${extendModal.weeks * 7} дней`}
                            </div>
                        </Space>
                    </Modal>
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

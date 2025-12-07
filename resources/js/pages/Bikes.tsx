import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import {
    DeleteOutlined,
    DownloadOutlined,
    EditOutlined,
    PlusOutlined,
    SearchOutlined,
} from '@ant-design/icons';
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
    Tabs,
} from 'antd';
import { FilterIcon } from 'lucide-react';
import { useState } from 'react';

import BikeFormDrawer from '@/components/ant-components/BikesFormDrawer';
import EquipmentFormDrawer from '@/components/ant-components/EquipmentFormDrawer';
import TariffFormDrawer from '@/components/ant-components/TariffFormDrawer';

import ruRU from 'antd/locale/ru_RU';
import { bikeColumns, equipmentColumns, tariffColumns } from './columnsConfig';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Велосипеды и оборудование', href: dashboard().url },
];

export default function Bikes() {
    const {
        bikes = { data: [] },
        equipment = { data: [] },
        tariffs = { data: [] },
        filters = {},
    } = usePage().props as any;

    const [search, setSearch] = useState<string>(filters.search || '');
    const [tab, setTab] = useState<string>(filters.tab || 'bikes');
    const [exportLoading, setExportLoading] = useState({
        bikes: false,
        equipment: false,
        tariffs: false,
    });

    const [drawer, setDrawer] = useState<{
        type: 'bikes' | 'equipment' | 'tariffs';
        visible: boolean;
        record: any;
    }>({ type: 'bikes', visible: false, record: null });

    const [deleteModal, setDeleteModal] = useState<{
        visible: boolean;
        record: any;
        type: string;
    }>({
        visible: false,
        record: null,
        type: '',
    });

    const currentData =
        tab === 'bikes' ? bikes : tab === 'equipment' ? equipment : tariffs;

    const go = (params: any, newTab?: string) => {
        const currentTab = newTab ?? tab;
        Inertia.get(
            '/bikes',
            { ...params, tab: currentTab, search },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleSearch = () => {
        go({ search });
    };
    const handleTabChange = (key: string) => {
        setTab(key);
        go({}, key);
    };
    const handleTableChange = (pagination: any) =>
        go({ page: pagination.current });

    // Функция экспорта для текущей вкладки
    const handleExport = (tableType: 'bikes' | 'equipment' | 'tariffs') => {
        // Устанавливаем состояние загрузки для соответствующей вкладки
        setExportLoading((prev) => ({ ...prev, [tableType]: true }));

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/export/${tableType}`;
        form.target = '_blank';
        form.style.display = 'none';

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (csrfToken) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);
        }

        const formatInput = document.createElement('input');
        formatInput.type = 'hidden';
        formatInput.name = 'format';
        formatInput.value = 'excel';
        form.appendChild(formatInput);

        // Добавляем поисковый запрос если есть
        if (search) {
            const searchInput = document.createElement('input');
            searchInput.type = 'hidden';
            searchInput.name = 'search';
            searchInput.value = search;
            form.appendChild(searchInput);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => {
            setExportLoading((prev) => ({ ...prev, [tableType]: false }));
            const labels = {
                bikes: 'велосипедов',
                equipment: 'аккумуляторов',
                tariffs: 'тарифов',
            };
            message.success(
                `Экспорт ${labels[tableType]} завершен. Файл открывается в новой вкладке.`,
            );
        }, 1500);
    };

    const openDrawer = (
        type: 'bikes' | 'equipment' | 'tariffs',
        record?: any,
    ) => {
        setDrawer({ type, visible: true, record: record || null });
    };
    const closeDrawer = () =>
        setDrawer({ ...drawer, visible: false, record: null });

    const openDeleteModal = (type: string, record: any) => {
        setDeleteModal({ visible: true, record, type });
    };

    const handleDelete = () => {
        const { record, type } = deleteModal;
        const url = `/${type}/${record.id}`;

        Inertia.delete(url, {
            preserveState: true,
            onSuccess: () => {
                message.success('Удалено');
                setDeleteModal({ visible: false, record: null, type: '' });
            },
            onError: () => message.error('Ошибка'),
        });
    };

    const onSubmit = (values: any) => {
        const { type, record } = drawer;
        const isEdit = !!record;
        const url = `/${type}${isEdit ? `/${record.id}` : ''}`;
        console.log(values);

        if (isEdit) {
            Inertia.put(url, values, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    console.log('УСПЕХ! Ответ сервера:', page);
                    message.success(isEdit ? 'Обновлено!' : 'Создано!');
                    closeDrawer();
                },
                onError: (errors) => {
                    console.log('ОШИБКИ валидации:', errors);
                    message.error('Проверьте поля');
                },
                onFinish: () => {
                    console.log('Запрос завершён');
                },
            });
        } else {
            Inertia.post(url, values, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    message.success(isEdit ? 'Обновлено' : 'Создано');
                    closeDrawer();
                    Inertia.reload({ only: [type] });
                },
                onError: () => message.error('Ошибка'),
            });
        }
    };

    const getColumns = (type: string) => {
        const openEdit = (r: any) => openDrawer(type as any, r);
        const openDelete = (r: any) => openDeleteModal(type, r);

        const cols = {
            bikes: bikeColumns(openEdit, openDelete),
            equipment: equipmentColumns(openEdit),
            tariffs: tariffColumns(openEdit, openDelete),
        }[type];

        return cols?.map((col: any) => {
            if (col.key === 'actions') {
                return {
                    ...col,
                    render: (_: any, record: any) => (
                        <Space>
                            <Button
                                type="text"
                                icon={<EditOutlined />}
                                onClick={() => openEdit(record)}
                            />
                            <Button
                                danger
                                type="text"
                                icon={<DeleteOutlined />}
                                onClick={() => openDelete(record)}
                            />
                        </Space>
                    ),
                };
            }
            return col;
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Велосипеды и оборудование" />
            <ConfigProvider
                locale={ruRU}
                theme={{
                    token: {
                        colorPrimary: 'oklch(0.205 0 0)',
                        borderRadius: 6,
                    },
                    components: {
                        Table: {
                            headerBg: 'oklch(0.97 0 0)',
                            rowHoverBg: 'oklch(0.97 0 0)',
                        },
                        Tabs: { cardBg: 'oklch(0.97 0 0)' },
                    },
                }}
            >
                <div style={{ padding: '24px' }}>
                    <Tabs activeKey={tab} onChange={handleTabChange}>
                        {[
                            {
                                key: 'bikes',
                                label: 'Велосипеды',
                                add: 'Добавить велосипед',
                                exportLabel: 'Экспорт велосипедов',
                                tableName: 'bikes',
                            },
                            {
                                key: 'equipment',
                                label: 'Аккумуляторы',
                                add: 'Добавить аккумулятор',
                                exportLabel: 'Экспорт аккумуляторов',
                                tableName: 'equipment',
                            },
                            {
                                key: 'tariffs',
                                label: 'Тарифы',
                                add: 'Добавить тариф',
                                exportLabel: 'Экспорт тарифов',
                                tableName: 'tariffs',
                            },
                        ].map(({ key, label, add, exportLabel, tableName }) => (
                            <Tabs.TabPane key={key} tab={label}>
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
                                        onClick={() => openDrawer(key as any)}
                                    >
                                        {add}
                                    </Button>
                                    <Space>
                                        <Input
                                            placeholder="Поиск..."
                                            prefix={<SearchOutlined />}
                                            value={search}
                                            onChange={(e) =>
                                                setSearch(e.target.value)
                                            }
                                            onPressEnter={handleSearch}
                                            allowClear
                                            style={{ width: 320 }}
                                        />
                                        <Button
                                            icon={<FilterIcon size={18} />}
                                        />

                                        {/* Кнопка экспорта для текущей вкладки */}
                                        <Button
                                            type="primary"
                                            icon={<DownloadOutlined />}
                                            loading={
                                                exportLoading[
                                                    key as keyof typeof exportLoading
                                                ]
                                            }
                                            onClick={() =>
                                                handleExport(tableName as any)
                                            }
                                            style={{ marginLeft: 8 }}
                                        >
                                            {exportLabel}
                                        </Button>
                                    </Space>
                                </Space>

                                <Table
                                    columns={getColumns(key)}
                                    dataSource={currentData?.data || []}
                                    rowKey="id"
                                    pagination={{
                                        current: currentData?.current_page || 1,
                                        pageSize: currentData?.per_page || 10,
                                        total: currentData?.total || 0,
                                        showQuickJumper: true,
                                    }}
                                    onChange={handleTableChange}
                                    scroll={{ x: 'max-content' }}
                                    locale={{ emptyText: 'Нет данных' }}
                                />
                            </Tabs.TabPane>
                        ))}
                    </Tabs>

                    {/* Drawers */}
                    {drawer.type === 'bikes' && (
                        <BikeFormDrawer
                            key={drawer.record?.id ?? 'new'}
                            visible={drawer.visible}
                            onClose={closeDrawer}
                            onSubmit={onSubmit}
                            initialValues={drawer.record}
                            isEditing={!!drawer.record}
                        />
                    )}
                    {drawer.type === 'equipment' && (
                        <EquipmentFormDrawer
                            key={drawer.record?.id ?? 'new'}
                            visible={drawer.visible}
                            onClose={closeDrawer}
                            onSubmit={onSubmit}
                            initialValues={drawer.record}
                            isEditing={!!drawer.record}
                        />
                    )}
                    {drawer.type === 'tariffs' && (
                        <TariffFormDrawer
                            key={drawer.record?.id ?? 'new'}
                            visible={drawer.visible}
                            onClose={closeDrawer}
                            onSubmit={onSubmit}
                            initialValues={drawer.record}
                            isEditing={!!drawer.record}
                        />
                    )}

                    {/* Модалка удаления */}
                    <Modal
                        title="Удалить?"
                        open={deleteModal.visible}
                        onCancel={() =>
                            setDeleteModal({
                                visible: false,
                                record: null,
                                type: '',
                            })
                        }
                        onOk={handleDelete}
                        okText="Удалить"
                        cancelText="Отмена"
                        okButtonProps={{ danger: true }}
                    >
                        <p>Вы уверены? Это действие нельзя отменить.</p>
                    </Modal>
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

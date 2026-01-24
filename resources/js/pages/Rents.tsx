import RentFormDrawer from '@/components/ant-components/RentsFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { DownloadOutlined, PlusOutlined } from '@ant-design/icons';
import { Inertia, PageProps as InertiaPageProps } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/react';
import {
    Button,
    ConfigProvider,
    Form,
    message,
    Modal,
    Select,
    Space,
    Table,
} from 'antd';
import ruRU from 'antd/locale/ru_RU';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import { rentsColumns } from './columnsConfig';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Аренда', href: dashboard().url },
];

interface Tariff {
    id: number;
    program: string;
}

interface Rent {
    id: number;
    client: any;
    bike: any;
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
    planned_end_date: any;
}

interface PageProps extends InertiaPageProps {
    rents: any;
    filters: any;
    clients_options: { id: number; name: string }[];
    bikes_options: any[];
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

    const [paidModal, setPaidModal] = useState<{
        visible: boolean;
        record: Rent | null;
    }>({
        visible: false,
        record: null,
    });

    const [bikeModal, setBikeModal] = useState<{
        visible: boolean;
        record: Rent | null;
        bikeId: string | null;
    }>({
        visible: false,
        record: null,
        bikeId: null,
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

    const [exportLoading, setExportLoading] = useState<boolean>(false); // Состояние для загрузки экспорта

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

    // Функция экспорта rentals
    const handleExport = () => {
        setExportLoading(true);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/export/rentals'; // URL для экспорта rentals
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

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => {
            setExportLoading(false);
            message.success(
                'Экспорт аренды завершен. Файл открывается в новой вкладке.',
            );
        }, 1500);
    };

    const openDrawer = (record?: Rent) => {
        setDrawer({ visible: true, record: record || null });
    };

    const closeDrawer = () => {
        setDrawer({ visible: false, record: null });
    };

    const openDeleteModal = (record: Rent) => {
        setDeleteModal({ visible: true, record });
    };

    const openPaidModal = (record: Rent) => {
        setPaidModal({ visible: true, record });
    };

    const openBikeModal = (record: Rent) => {
        setBikeModal({ visible: true, record, bikeId: null });
    };

    const handleDelete = () => {
        if (!deleteModal.record) return;
        console.log(deleteModal.record.id);
        Inertia.delete(`/rents/${deleteModal.record.id}`, {
            preserveState: true,
            onSuccess: () => {
                message.success('Аренда удалена');
                setDeleteModal({ visible: false, record: null });
            },
            onError: () => message.error('Ошибка удаления'),
        });
    };

    const handleChangeBike = () => {
        if (!bikeModal.record) return;
        Inertia.post(
            `/rentals/${bikeModal?.record.id}/cancel-with-bike-change`,
            { new_bike_id: bikeModal.bikeId },
            {
                preserveState: true,
                onSuccess: () => {
                    message.success('Велосипед изменен');
                    setDeleteModal({ visible: false, record: null });
                },
                onError: () => message.error('Ошибка изменения'),
            },
        );
    };

    const handlePaid = () => {
        if (!paidModal.record) return;
        Inertia.post(
            `/rents/${paidModal.record.id}/mark-paid`,
            {},
            {
                preserveState: true,
                onSuccess: () => {
                    message.success('Аренда оплачена');
                    setDeleteModal({ visible: false, record: null });
                },
                onError: () => message.error('Ошибка удаления'),
            },
        );
    };

    // ВАЖНО: Используем Modal.confirm ИЗ ConfigProvider
    const openExtendModal = (record: Rent) => {
        setExtendModal({ visible: true, record, weeks: 1 });
    };

    const closeExtendModal = () => {
        setExtendModal({ visible: false, record: null, weeks: 1 });
    };

    const onSubmit = (values: any) => {
        const payload = {
            ...values,
            start_date: dayjs(values.start_date).format('YYYY-MM-DD HH:mm:ss'),
            planned_end_date: dayjs(values.planned_end_date).format(
                'YYYY-MM-DD HH:mm:ss',
            ),
            actual_end_date: dayjs(values.actual_end_date).format(
                'YYYY-MM-DD HH:mm:ss',
            ),
        };
        const isEdit = !!drawer.record;
        const url = isEdit ? `/rents/${drawer.record!.id}` : '/rents';
        console.log(payload);
        if (isEdit) {
            if (payload.status === 'active') {
                changeRent(payload);
            }
            if (payload.status === 'completed_early') {
                completeEarly({
                    completion_type: payload.completion_type,
                    note: payload.note,
                });
            }
            if (payload.status === 'completed') {
                complete();
            }
        } else {
            Inertia.post(url, payload, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    message.success(isEdit ? 'Обновлено' : 'Создано');
                    closeDrawer();
                    // Inertia.reload();
                },
                onError: () => message.error('Ошибка'),
            });
        }
    };

    function changeRent(payload: any) {
        Inertia.put(`/rents/${drawer.record!.id}`, payload, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                console.log('УСПЕХ! Ответ сервера:', page);
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
    }

    function completeEarly(payload: any) {
        Inertia.post(`/rents/${drawer.record!.id}/complete-early`, payload, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                console.log('УСПЕХ! Ответ сервера:', page);
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
    }

    function complete() {
        Inertia.post(
            `/rents/${drawer.record!.id}/complete`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    console.log('УСПЕХ! Ответ сервера:', page);
                    closeDrawer();
                },
                onError: (errors) => {
                    console.log('ОШИБКИ валидации:', errors);
                    message.error('Проверьте поля');
                },
                onFinish: () => {
                    console.log('Запрос завершён');
                },
            },
        );
    }

    const getDocument = (rentalId: string | number) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/rentals/${rentalId}/generate-contract`;
        form.target = '_blank';
        form.style.display = 'none';

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        if (csrfToken) {
            const input = document.createElement('input');
            input.name = '_token';
            input.value = csrfToken;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        message.success('Договор открывается в новой вкладке');
    };

    const getDocumentPDF = (rentalId: string | number) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/rentals/${rentalId}/contract/pdf`;
        form.target = '_blank';
        form.style.display = 'none';

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        if (csrfToken) {
            const input = document.createElement('input');
            input.name = '_token';
            input.value = csrfToken;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        message.success('Договор открывается в новой вкладке');
    };

    const columns = rentsColumns(
        openDrawer,
        openExtendModal,
        openDeleteModal,
        openPaidModal,
        getDocument,
        getDocumentPDF,
        openBikeModal,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Аренда" />
            {/* ВСЁ ВНУТРИ ConfigProvider */}
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
                            {/* Кнопка экспорта аренды */}
                            <Button
                                type="primary"
                                icon={<DownloadOutlined />}
                                loading={exportLoading}
                                onClick={handleExport}
                                style={{ marginLeft: 8 }}
                            >
                                Экспорт в Excel
                            </Button>

                            {/* <Input
                                placeholder="Поиск по клиенту, примечанию..."
                                prefix={<SearchOutlined />}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onPressEnter={handleSearch}
                                allowClear
                                style={{ width: 320 }}
                            />
                            <Button icon={<FilterIcon size={18} />} /> */}
                        </Space>
                    </Space>

                    <Table
                        columns={columns}
                        dataSource={rents?.data}
                        rowKey="id"
                        pagination={{
                            current: rents?.meta?.current_page || 1,
                            pageSize: rents?.meta?.per_page || 10,
                            total: rents?.meta?.total || 0,
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
                        title="Отметить аренду как оплаченную?"
                        open={paidModal.visible}
                        onCancel={() =>
                            setPaidModal({ visible: false, record: null })
                        }
                        onOk={handlePaid}
                        okText="Оплачена"
                        cancelText="Отмена"
                    >
                        <p>Вы уверены? Это действие нельзя отменить.</p>
                    </Modal>

                    <Modal
                        title="Смена велосипеда"
                        open={bikeModal.visible}
                        onCancel={() =>
                            setBikeModal({
                                visible: false,
                                record: null,
                                bikeId: null,
                            })
                        }
                        onOk={handleChangeBike}
                        okText="Сменить"
                        cancelText="Отмена"
                    >
                        <Form.Item
                            label="Велосипед"
                            style={{ marginBottom: 0 }}
                        >
                            <Select
                                value={bikeModal.bikeId}
                                onChange={(value) =>
                                    setBikeModal((prev) => ({
                                        ...prev,
                                        bikeId: value,
                                    }))
                                }
                                style={{ width: '100%' }}
                                options={bikes_options
                                    .filter((item) => item.status === 'free')
                                    .map((item) => {
                                        return {
                                            value: item.id,
                                            label: `Номер вела: ${item?.bike_number}, номер рамы: ${item?.frame_number}`,
                                        };
                                    })}
                            ></Select>
                        </Form.Item>
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
                            const parsed = dayjs(
                                extendModal.record?.planned_end_date,
                            );
                            const end_date = parsed.isValid()
                                ? parsed
                                      .add(days, 'day')
                                      .format('YYYY-MM-DD HH:mm:ss')
                                : extendModal.record?.planned_end_date;
                            Inertia.put(
                                `/rents/${extendModal.record.id}`,
                                { planned_end_date: end_date },
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
                                {extendModal.record?.client.full_name}
                            </div>
                            <div>
                                <strong>Велосипед:</strong>{' '}
                                {extendModal.record?.bike.bike_number}
                            </div>
                            <div>
                                <strong>Текущая дата окончания:</strong>{' '}
                                {extendModal.record?.planned_end_date
                                    ? dayjs
                                          .utc(
                                              extendModal.record
                                                  .planned_end_date,
                                          )
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

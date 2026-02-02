import RentFormDrawer from '@/components/ant-components/RentsFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import {
    DownloadOutlined,
    FilterOutlined,
    PlusOutlined,
    SearchOutlined,
} from '@ant-design/icons';
import { Inertia, PageProps as InertiaPageProps } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/react';
import {
    Button,
    Card,
    Checkbox,
    Col,
    ConfigProvider,
    DatePicker,
    Form,
    Input,
    InputNumber,
    message,
    Modal,
    Row,
    Select,
    Space,
    Table,
    Tag,
} from 'antd';
import ruRU from 'antd/locale/ru_RU';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import { rentsColumns } from './columnsConfig';

const { RangePicker } = DatePicker;

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
    status: string;
    paid_status: string;
    total_cost: number;
}

interface PageProps extends InertiaPageProps {
    rents: any;
    filters: any;
    clients_options: { id: number; name: string; full_name: string }[];
    bikes_options: any[];
    tariffs_options: {
        id: number;
        program: string;
        price_week1: number;
        price_week2: number;
        price_month: number;
    }[];
}

interface FilterValues {
    search: string;
    client_id: number[];
    bike_id: number[];
    tariff_id: number[];
    status: string[];
    paid_status: string[];
    min_cost: number | null;
    max_cost: number | null;
    date_range: [dayjs.Dayjs, dayjs.Dayjs] | null;
    has_note: boolean | null;
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
    const [showFilters, setShowFilters] = useState<boolean>(false);
    const [filterValues, setFilterValues] = useState<FilterValues>({
        search: filters.search || '',
        client_id: filters.client_id
            ? filters.client_id.split(',').map(Number)
            : [],
        bike_id: filters.bike_id ? filters.bike_id.split(',').map(Number) : [],
        tariff_id: filters.tariff_id
            ? filters.tariff_id.split(',').map(Number)
            : [],
        status: filters.status ? filters.status.split(',') : [],
        paid_status: filters.paid_status ? filters.paid_status.split(',') : [],
        min_cost: filters.min_cost ? Number(filters.min_cost) : null,
        max_cost: filters.max_cost ? Number(filters.max_cost) : null,
        date_range:
            filters.start_date && filters.end_date
                ? [dayjs(filters.start_date), dayjs(filters.end_date)]
                : null,
        has_note: filters.has_note ? filters.has_note === 'true' : null,
    });

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

    const [exportLoading, setExportLoading] = useState<boolean>(false);
    const [form] = Form.useForm();

    useEffect(() => {
        if (filters.search !== search) setSearch(filters.search || '');
        // Восстанавливаем значения фильтров из URL
        const newFilterValues: FilterValues = {
            search: filters.search || '',
            client_id: filters.client_id
                ? filters.client_id.split(',').map(Number)
                : [],
            bike_id: filters.bike_id
                ? filters.bike_id.split(',').map(Number)
                : [],
            tariff_id: filters.tariff_id
                ? filters.tariff_id.split(',').map(Number)
                : [],
            status: filters.status ? filters.status.split(',') : [],
            paid_status: filters.paid_status
                ? filters.paid_status.split(',')
                : [],
            min_cost: filters.min_cost ? Number(filters.min_cost) : null,
            max_cost: filters.max_cost ? Number(filters.max_cost) : null,
            date_range:
                filters.start_date && filters.end_date
                    ? [dayjs(filters.start_date), dayjs(filters.end_date)]
                    : null,
            has_note: filters.has_note ? filters.has_note === 'true' : null,
        };
        setFilterValues(newFilterValues);
        form.setFieldsValue(newFilterValues);
    }, [filters]);

    const go = (params: any = {}) => {
        const filteredParams = Object.fromEntries(
            Object.entries(params).filter(
                ([_, v]) => v !== undefined && v !== null && v !== '',
            ),
        );
        Inertia.get(
            '/rents',
            { ...filteredParams },
            { preserveState: true, replace: true },
        );
    };

    const handleSearch = () => {
        go({ search: filterValues.search });
    };

    const handleApplyFilters = (values: FilterValues) => {
        const params: any = {};

        if (values.search) params.search = values.search;
        if (values.client_id && values.client_id.length > 0)
            params.client_id = values.client_id.join(',');
        if (values.bike_id && values.bike_id.length > 0)
            params.bike_id = values.bike_id.join(',');
        if (values.tariff_id && values.tariff_id.length > 0)
            params.tariff_id = values.tariff_id.join(',');
        if (values.status && values.status.length > 0)
            params.status = values.status.join(',');
        if (values.paid_status && values.paid_status.length > 0)
            params.paid_status = values.paid_status.join(',');
        if (values.min_cost !== null) params.min_cost = values.min_cost;
        if (values.max_cost !== null) params.max_cost = values.max_cost;
        if (values.date_range && values.date_range[0] && values.date_range[1]) {
            params.start_date = values.date_range[0].format('YYYY-MM-DD');
            params.end_date = values.date_range[1].format('YYYY-MM-DD');
        }
        if (values.has_note !== null)
            params.has_note = values.has_note.toString();

        go(params);
        setShowFilters(false);
    };

    const handleResetFilters = () => {
        form.resetFields();
        const resetValues = {
            search: '',
            client_id: [],
            bike_id: [],
            tariff_id: [],
            status: [],
            paid_status: [],
            min_cost: null,
            max_cost: null,
            date_range: null,
            has_note: null,
        };
        setFilterValues(resetValues);
        go({});
        setShowFilters(false);
    };

    const handleTableChange = (pagination: any) =>
        go({ page: pagination.current });

    const handleExport = () => {
        setExportLoading(true);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/export/rentals';
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

        Object.entries(filterValues).forEach(([key, value]) => {
            if (
                value !== null &&
                value !== '' &&
                !(Array.isArray(value) && value.length === 0)
            ) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                if (Array.isArray(value)) {
                    input.value = value.join(',');
                } else if (typeof value === 'boolean') {
                    input.value = value.toString();
                } else {
                    input.value = value;
                }
                form.appendChild(input);
            }
        });

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
                },
                onError: () => message.error('Ошибка'),
            });
        }
    };

    function changeRent(payload: any) {
        Inertia.put(
            `/rents/${drawer.record!.id}`,
            { planned_end_date: payload.planned_end_date },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    closeDrawer();
                },
                onError: (errors) => {
                    console.log('ОШИБКИ валидации:', errors);
                    message.error('Проверьте поля');
                },
            },
        );
    }

    function completeEarly(payload: any) {
        Inertia.post(`/rents/${drawer.record!.id}/complete-early`, payload, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                closeDrawer();
            },
            onError: (errors) => {
                console.log('ОШИБКИ валидации:', errors);
                message.error('Проверьте поля');
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
                onSuccess: () => {
                    closeDrawer();
                },
                onError: (errors) => {
                    console.log('ОШИБКИ валидации:', errors);
                    message.error('Проверьте поля');
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

    const statusOptions = [
        { label: 'Активна', value: 'active' },
        { label: 'Завершена', value: 'completed' },
        { label: 'Завершена заранее', value: 'completed_early' },
        { label: 'Отменена', value: 'cancelled' },
    ];

    const paidStatusOptions = [
        { label: 'Оплачено', value: 'paid' },
        { label: 'Не оплачено', value: 'unpaid' },
        { label: 'Частично оплачено', value: 'partially_paid' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Аренда" />
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
                            <Button
                                icon={<FilterOutlined />}
                                onClick={() => setShowFilters(!showFilters)}
                                type={showFilters ? 'primary' : 'default'}
                            >
                                Фильтры
                            </Button>

                            <Input
                                placeholder="Поиск по клиенту, примечанию..."
                                prefix={<SearchOutlined />}
                                value={filterValues.search}
                                onChange={(e) => {
                                    setFilterValues((prev) => ({
                                        ...prev,
                                        search: e.target.value,
                                    }));
                                }}
                                onPressEnter={handleSearch}
                                allowClear
                                style={{ width: 320 }}
                            />

                            <Button
                                type="primary"
                                icon={<DownloadOutlined />}
                                loading={exportLoading}
                                onClick={handleExport}
                                style={{ marginLeft: 8 }}
                            >
                                Экспорт
                            </Button>
                        </Space>
                    </Space>

                    {showFilters && (
                        <Card
                            title="Фильтры"
                            size="small"
                            style={{ marginBottom: 16 }}
                            extra={
                                <Space>
                                    <Button
                                        size="small"
                                        onClick={handleResetFilters}
                                    >
                                        Сбросить
                                    </Button>
                                    <Button
                                        type="primary"
                                        size="small"
                                        onClick={() => form.submit()}
                                    >
                                        Применить
                                    </Button>
                                </Space>
                            }
                        >
                            <Form
                                form={form}
                                layout="vertical"
                                initialValues={filterValues}
                                onFinish={handleApplyFilters}
                            >
                                <Row gutter={[16, 16]}>
                                    <Col span={8}>
                                        <Form.Item
                                            label="Клиенты"
                                            name="client_id"
                                        >
                                            <Select
                                                mode="multiple"
                                                placeholder="Выберите клиентов"
                                                options={clients_options.map(
                                                    (client) => ({
                                                        value: client.id,
                                                        label: (
                                                            client?.custom_fields ||
                                                            []
                                                        )?.length
                                                            ? `${
                                                                  client?.custom_fields.find(
                                                                      (item) =>
                                                                          item.field_name ===
                                                                          'last_name',
                                                                  )?.field_value
                                                              } ${
                                                                  client?.custom_fields.find(
                                                                      (item) =>
                                                                          item.field_name ===
                                                                          'first_name',
                                                                  )?.field_value
                                                              }`
                                                            : client?.name,
                                                    }),
                                                )}
                                                allowClear
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Велосипеды"
                                            name="bike_id"
                                        >
                                            <Select
                                                mode="multiple"
                                                placeholder="Выберите велосипеды"
                                                options={bikes_options.map(
                                                    (bike) => ({
                                                        value: bike.id,
                                                        label: `№${bike.bike_number} (рама: ${bike.frame_number})`,
                                                    }),
                                                )}
                                                allowClear
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Тарифы"
                                            name="tariff_id"
                                        >
                                            <Select
                                                mode="multiple"
                                                placeholder="Выберите тарифы"
                                                options={tariffs_options.map(
                                                    (tariff) => ({
                                                        value: tariff.id,
                                                        label: tariff.program,
                                                    }),
                                                )}
                                                allowClear
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Статус аренды"
                                            name="status"
                                        >
                                            <Select
                                                mode="multiple"
                                                placeholder="Выберите статусы"
                                                options={statusOptions}
                                                allowClear
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Статус оплаты"
                                            name="paid_status"
                                        >
                                            <Select
                                                mode="multiple"
                                                placeholder="Выберите статусы оплаты"
                                                options={paidStatusOptions}
                                                allowClear
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Дата аренды"
                                            name="date_range"
                                        >
                                            <RangePicker
                                                style={{ width: '100%' }}
                                                format="DD.MM.YYYY"
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Стоимость от"
                                            name="min_cost"
                                        >
                                            <InputNumber
                                                placeholder="Минимальная стоимость"
                                                style={{ width: '100%' }}
                                                min={0}
                                                addonAfter="₽"
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Стоимость до"
                                            name="max_cost"
                                        >
                                            <InputNumber
                                                placeholder="Максимальная стоимость"
                                                style={{ width: '100%' }}
                                                min={0}
                                                addonAfter="₽"
                                            />
                                        </Form.Item>
                                    </Col>

                                    <Col span={8}>
                                        <Form.Item
                                            label="Примечание"
                                            name="has_note"
                                            valuePropName="checked"
                                        >
                                            <Checkbox.Group>
                                                <Checkbox value={true}>
                                                    Есть примечание
                                                </Checkbox>
                                                <Checkbox value={false}>
                                                    Без примечания
                                                </Checkbox>
                                            </Checkbox.Group>
                                        </Form.Item>
                                    </Col>
                                </Row>
                            </Form>
                        </Card>
                    )}

                    {/* Отображение активных фильтров */}
                    {Object.entries(filterValues).some(
                        ([key, value]) =>
                            key !== 'search' &&
                            value !== null &&
                            value !== '' &&
                            !(Array.isArray(value) && value.length === 0),
                    ) && (
                        <div style={{ marginBottom: 16 }}>
                            <Space wrap>
                                <span>Активные фильтры:</span>
                                {filterValues.client_id &&
                                    filterValues.client_id.length > 0 && (
                                        <Tag
                                            closable
                                            onClose={() => {
                                                form.setFieldValue(
                                                    'client_id',
                                                    [],
                                                );
                                                handleApplyFilters({
                                                    ...filterValues,
                                                    client_id: [],
                                                });
                                            }}
                                        >
                                            Клиенты:{' '}
                                            {filterValues.client_id.length}
                                        </Tag>
                                    )}
                                {filterValues.bike_id &&
                                    filterValues.bike_id.length > 0 && (
                                        <Tag
                                            closable
                                            onClose={() => {
                                                form.setFieldValue(
                                                    'bike_id',
                                                    [],
                                                );
                                                handleApplyFilters({
                                                    ...filterValues,
                                                    bike_id: [],
                                                });
                                            }}
                                        >
                                            Велосипеды:{' '}
                                            {filterValues.bike_id.length}
                                        </Tag>
                                    )}
                                {filterValues.status &&
                                    filterValues.status.length > 0 && (
                                        <Tag
                                            closable
                                            onClose={() => {
                                                form.setFieldValue(
                                                    'status',
                                                    [],
                                                );
                                                handleApplyFilters({
                                                    ...filterValues,
                                                    status: [],
                                                });
                                            }}
                                        >
                                            Статусы:{' '}
                                            {filterValues.status
                                                .map(
                                                    (s) =>
                                                        statusOptions.find(
                                                            (o) =>
                                                                o.value === s,
                                                        )?.label || s,
                                                )
                                                .join(', ')}
                                        </Tag>
                                    )}
                                {filterValues.paid_status &&
                                    filterValues.paid_status.length > 0 && (
                                        <Tag
                                            closable
                                            onClose={() => {
                                                form.setFieldValue(
                                                    'paid_status',
                                                    [],
                                                );
                                                handleApplyFilters({
                                                    ...filterValues,
                                                    paid_status: [],
                                                });
                                            }}
                                        >
                                            Оплата:{' '}
                                            {filterValues.paid_status
                                                .map(
                                                    (s) =>
                                                        paidStatusOptions.find(
                                                            (o) =>
                                                                o.value === s,
                                                        )?.label || s,
                                                )
                                                .join(', ')}
                                        </Tag>
                                    )}
                                {(filterValues.min_cost !== null ||
                                    filterValues.max_cost !== null) && (
                                    <Tag
                                        closable
                                        onClose={() => {
                                            form.setFieldValue(
                                                ['min_cost', 'max_cost'],
                                                [null, null],
                                            );
                                            handleApplyFilters({
                                                ...filterValues,
                                                min_cost: null,
                                                max_cost: null,
                                            });
                                        }}
                                    >
                                        Стоимость:
                                        {filterValues.min_cost !== null
                                            ? ` от ${filterValues.min_cost}₽`
                                            : ''}
                                        {filterValues.max_cost !== null
                                            ? ` до ${filterValues.max_cost}₽`
                                            : ''}
                                    </Tag>
                                )}
                                {filterValues.date_range && (
                                    <Tag
                                        closable
                                        onClose={() => {
                                            form.setFieldValue(
                                                'date_range',
                                                null,
                                            );
                                            handleApplyFilters({
                                                ...filterValues,
                                                date_range: null,
                                            });
                                        }}
                                    >
                                        Дата:{' '}
                                        {filterValues.date_range[0].format(
                                            'DD.MM.YYYY',
                                        )}{' '}
                                        -{' '}
                                        {filterValues.date_range[1].format(
                                            'DD.MM.YYYY',
                                        )}
                                    </Tag>
                                )}
                                {filterValues.has_note !== null && (
                                    <Tag
                                        closable
                                        onClose={() => {
                                            form.setFieldValue(
                                                'has_note',
                                                null,
                                            );
                                            handleApplyFilters({
                                                ...filterValues,
                                                has_note: null,
                                            });
                                        }}
                                    >
                                        Примечание:{' '}
                                        {filterValues.has_note ? 'Есть' : 'Нет'}
                                    </Tag>
                                )}
                                <Button
                                    type="link"
                                    size="small"
                                    onClick={handleResetFilters}
                                >
                                    Сбросить все
                                </Button>
                            </Space>
                        </div>
                    )}

                    <Table
                        columns={columns}
                        dataSource={rents?.data}
                        rowKey="id"
                        pagination={{
                            current: rents?.meta?.current_page || 1,
                            pageSize: rents?.meta?.per_page || 10,
                            total: rents?.meta?.total || 0,
                            showQuickJumper: true,
                            showSizeChanger: true,
                            showTotal: (total, range) =>
                                `${range[0]}-${range[1]} из ${total} аренд`,
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

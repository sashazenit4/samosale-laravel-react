import PaymentFormDrawer from '@/components/ant-components/PaymentsFormDrawer';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import {
    DownloadOutlined,
    PlusOutlined,
    SearchOutlined,
} from '@ant-design/icons';
import { Inertia } from '@inertiajs/inertia';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Button,
    ConfigProvider,
    DatePicker,
    Form,
    Input,
    Modal,
    Select,
    Space,
    Table,
    message,
} from 'antd';
import ruRU from 'antd/locale/ru_RU';
import { CoinsIcon } from 'lucide-react';
import { useState } from 'react';
import { paymentsColumns } from './columnsConfig';

const { RangePicker } = DatePicker;
const { Option } = Select;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Платежи', href: dashboard().url },
];

const paymentStatusOptions = [
    { value: '', label: 'Все статусы' },
    { value: 'paid', label: 'Оплачено' },
    { value: 'unpaid', label: 'Не оплачено' },
    { value: 'partially_paid', label: 'Частично оплачено' },
];

// Статусы транзакций для фильтра
const transactionStatusOptions = [
    { value: 'pending', label: 'Ожидание' },
    { value: 'processing', label: 'В обработке' },
    { value: 'completed', label: 'Завершено' },
    { value: 'failed', label: 'Ошибка' },
    { value: 'expired', label: 'Просрочено' },
    { value: 'cancelled', label: 'Отменено' },
];

export default function Payments() {
    const { payments, filters = {}, clients_options } = usePage().props as any;
    const [search, setSearch] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || '');
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [editingPayment, setEditingPayment] = useState<any>(null);
    const [exportLoading, setExportLoading] = useState(false);
    const [exportModalVisible, setExportModalVisible] = useState(false);
    const [exportForm] = Form.useForm();

    const applyFilters = () => {
        const params: any = {};

        if (search) {
            params.search = search;
        }

        if (statusFilter) {
            params.status = statusFilter;
        }

        router.get('/payments', params, { preserveState: true, replace: true });
    };

    const handleSearch = () => {
        applyFilters();
    };

    const handleStatusChange = (value: string) => {
        setStatusFilter(value);
        const params: any = {};

        if (search) {
            params.search = search;
        }

        if (value) {
            params.status = value;
        }

        router.get('/payments', params, { preserveState: true, replace: true });
    };

    // Функция экспорта платежей
    const handleExportPayments = () => {
        setExportLoading(true);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/export/payments';
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

        if (search) {
            const searchInput = document.createElement('input');
            searchInput.type = 'hidden';
            searchInput.name = 'search';
            searchInput.value = search;
            form.appendChild(searchInput);
        }

        if (statusFilter) {
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = statusFilter;
            form.appendChild(statusInput);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => {
            setExportLoading(false);
            message.success(
                'Экспорт платежей завершен. Файл открывается в новой вкладке.',
            );
        }, 1500);
    };

    // Функция экспорта транзакций с фильтрами
    const handleExportTransactions = () => {
        setExportModalVisible(true);
    };

    const handleExportTransactionsSubmit = () => {
        exportForm.validateFields().then((values) => {
            const { status, date_range } = values;

            // Формируем параметры запроса
            const params = new URLSearchParams();

            if (status) {
                params.append('status', status);
            }

            if (date_range && date_range.length === 2) {
                const [start, end] = date_range;
                params.append('date_from', start.format('YYYY-MM-DD'));
                params.append('date_to', end.format('YYYY-MM-DD'));
            }

            // Формат по умолчанию
            params.append('format', 'excel');

            // Создаем форму для отправки
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = '/transactions/export/direct';
            form.target = '_blank';
            form.style.display = 'none';

            // Добавляем CSRF токен для GET запроса (если нужен)
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

            // Добавляем параметры как скрытые поля
            for (const [key, value] of params.entries()) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            setExportModalVisible(false);
            exportForm.resetFields();

            message.success(
                'Экспорт транзакций начался. Файл откроется в новой вкладке.',
            );
        });
    };

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Платежи" />

            <ConfigProvider locale={ruRU}>
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

                        <Space>
                            <Input
                                placeholder="Поиск..."
                                prefix={<SearchOutlined />}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onPressEnter={handleSearch}
                                allowClear
                                onClear={() => {
                                    setSearch('');
                                    applyFilters();
                                }}
                                style={{ width: 200 }}
                            />

                            <Select
                                placeholder="Статус платежа"
                                value={statusFilter || undefined}
                                onChange={handleStatusChange}
                                style={{ width: 180 }}
                                allowClear
                                onClear={() => handleStatusChange('')}
                            >
                                {paymentStatusOptions.map((option) => (
                                    <Option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </Option>
                                ))}
                            </Select>

                            {/* Кнопка экспорта платежей */}
                            <Button
                                type="primary"
                                icon={<DownloadOutlined />}
                                loading={exportLoading}
                                onClick={handleExportPayments}
                                style={{ marginLeft: 8 }}
                            >
                                Экспорт платежей
                            </Button>

                            {/* Кнопка экспорта транзакций */}
                            <Button
                                type="default"
                                icon={<DownloadOutlined />}
                                onClick={handleExportTransactions}
                                style={{ marginLeft: 8 }}
                            >
                                Экспорт транзакций
                            </Button>
                        </Space>
                    </Space>

                    <Table
                        columns={paymentsColumns(openDrawer, handleDelete)}
                        dataSource={payments?.data || []}
                        rowKey="id"
                        scroll={{ x: 1400 }}
                        pagination={{
                            current: payments.meta?.current_page || 1,
                            pageSize: payments.meta?.per_page || 15,
                            total: payments.meta?.total || 0,
                        }}
                        onChange={(pagination) => {
                            const params: any = {
                                page: pagination.current,
                            };

                            if (search) {
                                params.search = search;
                            }

                            if (statusFilter) {
                                params.status = statusFilter;
                            }

                            router.get('/payments', params, {
                                preserveState: true,
                            });
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

                    {/* Модальное окно для экспорта транзакций */}
                    <Modal
                        title="Экспорт транзакций"
                        open={exportModalVisible}
                        onCancel={() => setExportModalVisible(false)}
                        onOk={handleExportTransactionsSubmit}
                        okText="Экспортировать"
                        cancelText="Отмена"
                        width={400}
                    >
                        <Form
                            form={exportForm}
                            layout="vertical"
                            initialValues={{
                                status: '',
                            }}
                        >
                            <Form.Item name="status" label="Статус транзакций">
                                <Select
                                    placeholder="Выберите статус"
                                    allowClear
                                >
                                    <Option value="">Все статусы</Option>
                                    {transactionStatusOptions.map((option) => (
                                        <Option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>

                            <Form.Item name="date_range" label="Период">
                                <RangePicker
                                    style={{ width: '100%' }}
                                    format="DD.MM.YYYY"
                                    placeholder={['Дата от', 'Дата до']}
                                />
                            </Form.Item>
                        </Form>
                    </Modal>
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

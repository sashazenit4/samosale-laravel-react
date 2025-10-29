import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { Head } from '@inertiajs/react';
import { Button, ConfigProvider, Input, Space, Table, Tabs } from 'antd';
import { FilterIcon } from 'lucide-react';
import { useState } from 'react';
import { bikeColumns, equipmentColumns, tariffColumns } from './columnsConfig';

// Импортируем Drawer-формы
import BikeFormDrawer from '@/components/ant-components/BikesFormDrawer';
import EquipmentFormDrawer from '@/components/ant-components/EquipmentFormDrawer';
import TariffFormDrawer from '@/components/ant-components/TariffFormDrawer';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Велосипеды и оборудование',
        href: dashboard().url,
    },
];

const mockBikes = [
    {
        id: 1,
        bike_number: 'BIKE001',
        frame_number: 'FRAME12345',
        status: 'rented',
        type: 'TRAK',
    },
    {
        id: 2,
        bike_number: 'BIKE002',
        frame_number: 'FRAME67890',
        status: 'free',
        type: 'MOVER',
    },
    {
        id: 3,
        bike_number: 'BIKE003',
        frame_number: 'FRAME54321',
        status: 'stolen',
        type: 'TRAK',
    },
];

const mockEquipment = [
    {
        id: 1,
        number: 'EQP001',
        status: 'free',
    },
    {
        id: 2,
        number: 'EQP002',
        status: 'rented',
    },
    {
        id: 3,
        number: 'EQP003',
        status: 'stolen',
    },
];

const mockTariffs = [
    {
        id: 1,
        program: 'regular',
        power: '500W',
        week_1: 1000,
        week_2: 950,
        week_3: 900,
        week_4: 850,
        month_1: 3200,
    },
    {
        id: 2,
        program: 'scooter',
        power: '750W',
        week_1: 1200,
        week_2: 1150,
        week_3: 1100,
        week_4: 1050,
        month_1: 4000,
    },
    {
        id: 3,
        program: 'cooper',
        power: '1000W',
        week_1: 1500,
        week_2: 1400,
        week_3: 1300,
        week_4: 1200,
        month_1: 4800,
    },
];

export default function BikesAndEquipment() {
    const [bikeSearch, setBikeSearch] = useState<string>('');
    const [equipmentSearch, setEquipmentSearch] = useState<string>('');
    const [tariffSearch, setTariffSearch] = useState<string>('');

    // Состояния для Drawer
    const [bikeDrawerVisible, setBikeDrawerVisible] = useState(false);
    const [equipmentDrawerVisible, setEquipmentDrawerVisible] = useState(false);
    const [tariffDrawerVisible, setTariffDrawerVisible] = useState(false);

    const [editingBike, setEditingBike] = useState<
        (typeof mockBikes)[0] | null
    >(null);
    const [editingEquipment, setEditingEquipment] = useState<
        (typeof mockEquipment)[0] | null
    >(null);
    const [editingTariff, setEditingTariff] = useState<
        (typeof mockTariffs)[0] | null
    >(null);

    // Фильтрация
    const filteredBikes = mockBikes.filter(
        (bike) =>
            bike.bike_number.toLowerCase().includes(bikeSearch.toLowerCase()) ||
            bike.frame_number
                .toLowerCase()
                .includes(bikeSearch.toLowerCase()) ||
            bike.type.toLowerCase().includes(bikeSearch.toLowerCase()),
    );

    const filteredEquipment = mockEquipment.filter((equipment) =>
        equipment.number.toLowerCase().includes(equipmentSearch.toLowerCase()),
    );

    const filteredTariffs = mockTariffs.filter((tariff) =>
        tariff.program.toLowerCase().includes(tariffSearch.toLowerCase()),
    );

    // Обработчики Drawer
    const openBikeDrawer = (bike?: (typeof mockBikes)[0]) => {
        setEditingBike(bike || null);
        setBikeDrawerVisible(true);
    };

    const closeBikeDrawer = () => {
        setBikeDrawerVisible(false);
        setEditingBike(null);
    };

    const openEquipmentDrawer = (equipment?: (typeof mockEquipment)[0]) => {
        setEditingEquipment(equipment || null);
        setEquipmentDrawerVisible(true);
    };

    const closeEquipmentDrawer = () => {
        setEquipmentDrawerVisible(false);
        setEditingEquipment(null);
    };

    const openTariffDrawer = (tariff?: (typeof mockTariffs)[0]) => {
        setEditingTariff(tariff || null);
        setTariffDrawerVisible(true);
    };

    const closeTariffDrawer = () => {
        setTariffDrawerVisible(false);
        setEditingTariff(null);
    };

    // Обработчики отправки
    const onBikeSubmit = (values: any) => {
        console.log('Создан/обновлён велосипед:', values);
        closeBikeDrawer();
    };

    const onEquipmentSubmit = (values: any) => {
        console.log('Создан/обновлён аккумулятор:', values);
        closeEquipmentDrawer();
    };

    const onTariffSubmit = (values: any) => {
        console.log('Создан/обновлён тариф:', values);
        closeTariffDrawer();
    };

    // Столбцы с передачей openDrawer
    const bikesColumns = bikeColumns(openBikeDrawer);
    const equipmentsColumns = equipmentColumns(openEquipmentDrawer);
    const tariffsColumns = tariffColumns(openTariffDrawer);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Велосипеды, оборудование и тарифы" />
            <ConfigProvider
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
                        Tabs: {
                            cardBg: 'oklch(0.97 0 0)',
                            itemColor: 'oklch(0.145 0 0)',
                            itemHoverColor: 'oklch(0.205 0 0)',
                            itemSelectedColor: 'oklch(0.205 0 0)',
                        },
                        Tag: {
                            defaultBg: 'oklch(0.97 0 0)',
                            defaultColor: 'oklch(0.145 0 0)',
                        },
                    },
                }}
            >
                <div style={{ padding: '24px' }}>
                    <Tabs
                        defaultActiveKey="bikes"
                        items={[
                            {
                                key: 'bikes',
                                label: 'Велосипеды',
                                children: (
                                    <div>
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
                                                onClick={() => openBikeDrawer()}
                                            >
                                                Добавить велосипед
                                            </Button>
                                            <Space size="large">
                                                <Input
                                                    placeholder="Поиск по номеру велосипеда, рамы или типу"
                                                    prefix={<SearchOutlined />}
                                                    value={bikeSearch}
                                                    onChange={(e) =>
                                                        setBikeSearch(
                                                            e.target.value,
                                                        )
                                                    }
                                                    allowClear
                                                />
                                                <Button
                                                    icon={
                                                        <FilterIcon size="18px" />
                                                    }
                                                />
                                            </Space>
                                        </Space>
                                        <Table
                                            columns={bikesColumns}
                                            dataSource={filteredBikes}
                                            rowKey="id"
                                            pagination={{ pageSize: 10 }}
                                            scroll={{ x: 'max-content' }}
                                            locale={{
                                                emptyText:
                                                    'Нет данных для отображения',
                                            }}
                                            bordered
                                        />
                                        <BikeFormDrawer
                                            visible={bikeDrawerVisible}
                                            onClose={closeBikeDrawer}
                                            onSubmit={onBikeSubmit}
                                            initialValues={editingBike}
                                            isEditing={!!editingBike}
                                        />
                                    </div>
                                ),
                            },
                            {
                                key: 'equipment',
                                label: 'Аккумуляторы',
                                children: (
                                    <div>
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
                                                onClick={() =>
                                                    openEquipmentDrawer()
                                                }
                                            >
                                                Добавить аккумулятор
                                            </Button>
                                            <Space size="large">
                                                <Input
                                                    placeholder="Поиск по номеру оборудования"
                                                    prefix={<SearchOutlined />}
                                                    value={equipmentSearch}
                                                    onChange={(e) =>
                                                        setEquipmentSearch(
                                                            e.target.value,
                                                        )
                                                    }
                                                    allowClear
                                                />
                                                <Button
                                                    icon={
                                                        <FilterIcon size="18px" />
                                                    }
                                                />
                                            </Space>
                                        </Space>
                                        <Table
                                            columns={equipmentsColumns}
                                            dataSource={filteredEquipment}
                                            rowKey="id"
                                            pagination={{ pageSize: 10 }}
                                            scroll={{ x: 'max-content' }}
                                            locale={{
                                                emptyText:
                                                    'Нет данных для отображения',
                                            }}
                                            bordered
                                        />
                                        <EquipmentFormDrawer
                                            visible={equipmentDrawerVisible}
                                            onClose={closeEquipmentDrawer}
                                            onSubmit={onEquipmentSubmit}
                                            initialValues={editingEquipment}
                                            isEditing={!!editingEquipment}
                                        />
                                    </div>
                                ),
                            },
                            {
                                key: 'tariffs',
                                label: 'Тарифы',
                                children: (
                                    <div>
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
                                                onClick={() =>
                                                    openTariffDrawer()
                                                }
                                            >
                                                Добавить тариф
                                            </Button>
                                            <Space size="large">
                                                <Input
                                                    placeholder="Поиск по программе"
                                                    prefix={<SearchOutlined />}
                                                    value={tariffSearch}
                                                    onChange={(e) =>
                                                        setTariffSearch(
                                                            e.target.value,
                                                        )
                                                    }
                                                    allowClear
                                                />
                                                <Button
                                                    icon={
                                                        <FilterIcon size="18px" />
                                                    }
                                                />
                                            </Space>
                                        </Space>
                                        <Table
                                            columns={tariffsColumns}
                                            dataSource={filteredTariffs}
                                            rowKey="id"
                                            pagination={{ pageSize: 10 }}
                                            scroll={{ x: 'max-content' }}
                                            locale={{
                                                emptyText:
                                                    'Нет данных для отображения',
                                            }}
                                            bordered
                                        />
                                        <TariffFormDrawer
                                            visible={tariffDrawerVisible}
                                            onClose={closeTariffDrawer}
                                            onSubmit={onTariffSubmit}
                                            initialValues={editingTariff}
                                            isEditing={!!editingTariff}
                                        />
                                    </div>
                                ),
                            },
                        ]}
                    />
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

import AppLayout from '@/layouts/app-layout';
import { SaveOutlined } from '@ant-design/icons';
import { Inertia } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/react';
import {
    Button,
    Card,
    ConfigProvider,
    Input,
    InputNumber,
    message,
    Space,
    Typography,
} from 'antd';
import ruRU from 'antd/locale/ru_RU';
import { useEffect, useState } from 'react';

const { Title, Text } = Typography;

interface BonusConfig {
    key: string;
    value: any;
    description: string;
}

declare global {
    interface Window {
        route: any;
    }
}

export default function BonusConfig() {
    const { configs: initialConfigs = [] } = usePage<{
        configs: BonusConfig[];
    }>().props;
    const [formData, setFormData] = useState<Record<string, any>>({});
    const [savingKey, setSavingKey] = useState<string | null>(null);

    useEffect(() => {
        const data: Record<string, any> = {};
        initialConfigs.forEach((c) => {
            data[c.key] = c.value;
        });
        setFormData(data);
    }, [initialConfigs]);

    const saveSection = (key: string) => {
        setSavingKey(key);

        Inertia.put(
            `/bonus-config/${key}`,
            { value: formData[key] },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    message.success('Сохранено');
                },
                onError: (errors) => {
                    console.log('Ошибка:', errors);
                    message.error('Не удалось сохранить');
                },
                onFinish: () => setSavingKey(null),
            },
        );
    };

    const updateField = (key: string, field: string, value: any) => {
        setFormData((prev) => ({
            ...prev,
            [key]: { ...prev[key], [field]: value },
        }));
    };

    const updateLevel = (index: number, field: string, value: any) => {
        setFormData((prev) => ({
            ...prev,
            bonus_levels: prev.bonus_levels.map((l: any, i: number) =>
                i === index ? { ...l, [field]: value } : l,
            ),
        }));
    };

    return (
        <AppLayout>
            <Head title="Реферальная программа" />

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
                }}
            >
                <div
                    style={{ padding: '24px', maxWidth: 900, margin: '0 auto' }}
                >
                    <Space
                        direction="vertical"
                        size="large"
                        style={{ width: '100%' }}
                    >
                        <div>
                            <Title level={2}>Реферальная программа</Title>
                            <Text type="secondary">
                                Редактирование бонусов и уровней лояльности
                            </Text>
                        </div>

                        {/* Welcome Bonus */}
                        <Card
                            title="Бонус за регистрацию без реферала"
                            extra={
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<SaveOutlined />}
                                    loading={savingKey === 'welcome_bonus'}
                                    onClick={() => saveSection('welcome_bonus')}
                                >
                                    Сохранить
                                </Button>
                            }
                        >
                            <Space align="baseline">
                                <Text style={{ width: 160 }}>Сумма:</Text>
                                <InputNumber
                                    min={0}
                                    value={formData.welcome_bonus?.amount ?? 0}
                                    onChange={(v) =>
                                        updateField(
                                            'welcome_bonus',
                                            'amount',
                                            v,
                                        )
                                    }
                                    addonAfter="₽"
                                    style={{ width: 200 }}
                                />
                            </Space>
                            <Text type="secondary">
                                {
                                    initialConfigs.find(
                                        (c) => c.key === 'welcome_bonus',
                                    )?.description
                                }
                            </Text>
                        </Card>

                        {/* Referral Bonus */}
                        <Card
                            title="Бонусы за приглашение"
                            extra={
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<SaveOutlined />}
                                    loading={savingKey === 'referral_bonus'}
                                    onClick={() =>
                                        saveSection('referral_bonus')
                                    }
                                >
                                    Сохранить
                                </Button>
                            }
                        >
                            <Space
                                direction="vertical"
                                size="middle"
                                style={{ width: '100%' }}
                            >
                                <Space align="baseline">
                                    <Text style={{ width: 160 }}>
                                        Приглашённому:
                                    </Text>
                                    <InputNumber
                                        min={0}
                                        value={
                                            formData.referral_bonus
                                                ?.referee_amount ?? 0
                                        }
                                        onChange={(v) =>
                                            updateField(
                                                'referral_bonus',
                                                'referee_amount',
                                                v,
                                            )
                                        }
                                        addonAfter="₽"
                                        style={{ width: 200 }}
                                    />
                                </Space>
                                <Space align="baseline">
                                    <Text style={{ width: 160 }}>
                                        Пригласившему:
                                    </Text>
                                    <InputNumber
                                        min={0}
                                        value={
                                            formData.referral_bonus
                                                ?.referrer_amount ?? 0
                                        }
                                        onChange={(v) =>
                                            updateField(
                                                'referral_bonus',
                                                'referrer_amount',
                                                v,
                                            )
                                        }
                                        addonAfter="₽"
                                        style={{ width: 200 }}
                                    />
                                </Space>
                            </Space>
                            <Text type="secondary">
                                {
                                    initialConfigs.find(
                                        (c) => c.key === 'referral_bonus',
                                    )?.description
                                }
                            </Text>
                        </Card>

                        {/* Payment Bonus */}
                        <Card
                            title="Бонус за оплату"
                            extra={
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<SaveOutlined />}
                                    loading={
                                        savingKey === 'payment_bonus_percentage'
                                    }
                                    onClick={() =>
                                        saveSection('payment_bonus_percentage')
                                    }
                                >
                                    Сохранить
                                </Button>
                            }
                        >
                            <Space align="baseline">
                                <Text style={{ width: 160 }}>Процент:</Text>
                                <InputNumber
                                    min={0}
                                    max={100}
                                    value={
                                        formData.payment_bonus_percentage
                                            ?.percentage ?? 0
                                    }
                                    onChange={(v) =>
                                        updateField(
                                            'payment_bonus_percentage',
                                            'percentage',
                                            v,
                                        )
                                    }
                                    addonAfter="%"
                                    style={{ width: 200 }}
                                />
                            </Space>
                            <Text type="secondary">
                                {
                                    initialConfigs.find(
                                        (c) =>
                                            c.key ===
                                            'payment_bonus_percentage',
                                    )?.description
                                }
                            </Text>
                        </Card>

                        {/* Bonus Levels */}
                        <Card
                            title="Уровни лояльности"
                            extra={
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<SaveOutlined />}
                                    loading={savingKey === 'bonus_levels'}
                                    onClick={() => saveSection('bonus_levels')}
                                >
                                    Сохранить все уровни
                                </Button>
                            }
                        >
                            {formData.bonus_levels?.map(
                                (level: any, index: number) => (
                                    <Card
                                        key={index}
                                        size="small"
                                        title={level.name}
                                        style={{ marginBottom: 16 }}
                                    >
                                        <Space
                                            direction="vertical"
                                            style={{ width: '100%' }}
                                        >
                                            <Input
                                                addonBefore="Название"
                                                value={level.name}
                                                onChange={(e) =>
                                                    updateLevel(
                                                        index,
                                                        'name',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <Space>
                                                <InputNumber
                                                    addonBefore="Мин. траты"
                                                    value={level.min_spent}
                                                    onChange={(v) =>
                                                        updateLevel(
                                                            index,
                                                            'min_spent',
                                                            v,
                                                        )
                                                    }
                                                    addonAfter="₽"
                                                />
                                                <InputNumber
                                                    addonBefore="Бонус"
                                                    value={
                                                        level.bonus_percentage
                                                    }
                                                    onChange={(v) =>
                                                        updateLevel(
                                                            index,
                                                            'bonus_percentage',
                                                            v,
                                                        )
                                                    }
                                                    addonAfter="%"
                                                />
                                            </Space>
                                        </Space>
                                    </Card>
                                ),
                            )}
                            <Text type="secondary">
                                {
                                    initialConfigs.find(
                                        (c) => c.key === 'bonus_levels',
                                    )?.description
                                }
                            </Text>
                        </Card>

                        {/* NEW: Bonus Lifetime Days */}
                        <Card
                            title="Время жизни бонуса"
                            extra={
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<SaveOutlined />}
                                    loading={
                                        savingKey === 'bonus_lifetime_days'
                                    }
                                    onClick={() =>
                                        saveSection('bonus_lifetime_days')
                                    }
                                >
                                    Сохранить
                                </Button>
                            }
                        >
                            <Space align="baseline">
                                <Text style={{ width: 160 }}>
                                    Срок действия:
                                </Text>
                                <InputNumber
                                    min={1}
                                    value={
                                        formData.bonus_lifetime_days?.days ?? 30
                                    }
                                    onChange={(v) =>
                                        updateField(
                                            'bonus_lifetime_days',
                                            'days',
                                            v,
                                        )
                                    }
                                    addonAfter="дней"
                                    style={{ width: 200 }}
                                />
                            </Space>
                            <Text type="secondary">
                                {
                                    initialConfigs.find(
                                        (c) => c.key === 'bonus_lifetime_days',
                                    )?.description
                                }
                            </Text>
                        </Card>

                        {/* NEW: Referral Bonus Condition */}
                        <Card
                            title="Условие для получения реферального бонуса"
                            extra={
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<SaveOutlined />}
                                    loading={
                                        savingKey === 'referral_bonus_condition'
                                    }
                                    onClick={() =>
                                        saveSection('referral_bonus_condition')
                                    }
                                >
                                    Сохранить
                                </Button>
                            }
                        >
                            <Space align="baseline">
                                <Text style={{ width: 160 }}>
                                    Минимальная сумма трат приглашенного:
                                </Text>
                                <InputNumber
                                    min={0}
                                    value={
                                        formData.referral_bonus_condition
                                            ?.referee_min_spent ?? 0
                                    }
                                    onChange={(v) =>
                                        updateField(
                                            'referral_bonus_condition',
                                            'referee_min_spent',
                                            v,
                                        )
                                    }
                                    addonAfter="₽"
                                    style={{ width: 200 }}
                                />
                            </Space>
                            <Text type="secondary">
                                {
                                    initialConfigs.find(
                                        (c) =>
                                            c.key ===
                                            'referral_bonus_condition',
                                    )?.description
                                }
                            </Text>
                        </Card>
                    </Space>
                </div>
            </ConfigProvider>
        </AppLayout>
    );
}

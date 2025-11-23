<?php

namespace App\Services;

use App\Models\Tariff;
use Carbon\Carbon;

class RentalPriceService
{
    /**
     * Расчет стоимости аренды на основе тарифа и периода
     */
    public function calculateRentalPrice(Tariff $tariff, Carbon $startDate, Carbon $endDate)
    {
        $days = $startDate->diffInDays($endDate);

        // Базовая стоимость по тарифу
        $basePrice = $this->calculateBasePrice($tariff, $days);

        // Детализированная разбивка по периодам
        $breakdown = $this->calculateBreakdown($tariff, $days, $startDate);

        return [
            'base_price' => $basePrice,
            'total_price' => $basePrice,
            'days' => $days,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Детализированная разбивка стоимости по периодам
     */
    private function calculateBreakdown(Tariff $tariff, int $days, Carbon $startDate = null): array
    {
        $breakdown = [];
        $remainingDays = $days;
        $currentDate = $startDate ? $startDate->copy() : null;

        // Для коротких периодов (до 28 дней) используем недельную логику
        if ($days <= 28) {
            $weekNumber = 1;

            while ($remainingDays > 0 && $weekNumber <= 4) {
                $weekPriceField = "price_week{$weekNumber}";
                $weekPrice = $tariff->$weekPriceField;
                $periodDays = min(7, $remainingDays);

                $description = $currentDate
                    ? "{$weekNumber} неделя (" . $currentDate->format('d.m') . " - " . $currentDate->copy()->addDays($periodDays)->format('d.m') . ")"
                    : "{$weekNumber} неделя";

                $breakdown[] = [
                    'type' => 'week',
                    'amount' => $weekPrice,
                    'description' => $description,
                    'days' => $periodDays
                ];

                $remainingDays -= $periodDays;
                $weekNumber++;

                if ($currentDate) {
                    $currentDate->addDays($periodDays);
                }
            }
        } else {
            // Для длинных периодов (больше 28 дней) используем месячную логику
            $fullMonths = floor($days / 30);
            $remainingDays = $days % 30;

            for ($i = 1; $i <= $fullMonths; $i++) {
                $description = $currentDate
                    ? "Месяц {$i} (" . $currentDate->format('d.m') . " - " . $currentDate->copy()->addMonth()->format('d.m') . ")"
                    : "Месяц {$i}";

                $breakdown[] = [
                    'type' => 'month',
                    'amount' => $tariff->price_month,
                    'description' => $description,
                    'days' => 30
                ];

                if ($currentDate) {
                    $currentDate->addMonth();
                }
            }

            // Добавляем оставшиеся дни как недельные периоды
            if ($remainingDays > 0) {
                $weekNumber = 1;

                while ($remainingDays > 0 && $weekNumber <= 4) {
                    $weekPriceField = "price_week{$weekNumber}";
                    $weekPrice = $tariff->$weekPriceField;
                    $periodDays = min(7, $remainingDays);

                    $description = $currentDate
                        ? "{$weekNumber} неделя (" . $currentDate->format('d.m') . " - " . $currentDate->copy()->addDays($periodDays)->format('d.m') . ")"
                        : "{$weekNumber} неделя";

                    $breakdown[] = [
                        'type' => 'week',
                        'amount' => $weekPrice,
                        'description' => $description,
                        'days' => $periodDays
                    ];

                    $remainingDays -= $periodDays;
                    $weekNumber++;

                    if ($currentDate) {
                        $currentDate->addDays($periodDays);
                    }
                }
            }
        }

        return $breakdown;
    }

    /**
     * Расчет базовой стоимости по тарифу
     */
    private function calculateBasePrice(Tariff $tariff, int $days)
    {
        if ($days <= 7) {
            return $tariff->price_week1;
        } elseif ($days <= 14) {
            return $tariff->price_week2 + $tariff->price_week1;
        } elseif ($days <= 21) {
            return $tariff->price_week3 + $tariff->price_week2 + $tariff->price_week1;
        } elseif ($days <= 28) {
            return $tariff->price_week4 + $tariff->price_week3 + $tariff->price_week2 + $tariff->price_week1;
        } else {
            // Расчет для периодов больше 28 дней
            return $this->calculateMonthlyPrice($tariff, $days);
        }
    }

    /**
     * Расчет стоимости для периодов больше 28 дней
     */
    private function calculateMonthlyPrice(Tariff $tariff, int $days)
    {
        // Количество полных месяцев
        $fullMonths = floor($days / 30);
        $remainingDays = $days % 30;

        $totalPrice = $fullMonths * $tariff->price_month;

        // Добавляем стоимость за оставшиеся дни по недельной логике
        if ($remainingDays > 0) {
            if ($remainingDays <= 7) {
                $totalPrice += $tariff->price_week1;
            } elseif ($remainingDays <= 14) {
                $totalPrice += $tariff->price_week1 + $tariff->price_week2;
            } elseif ($remainingDays <= 21) {
                $totalPrice += $tariff->price_week1 + $tariff->price_week2 + $tariff->price_week3;
            } else {
                $totalPrice += $tariff->price_month;
            }
        }

        return $totalPrice;
    }

    /**
     * Расчет суммы возврата при досрочном завершении
     */
    public function calculateRefundAmount($totalCost, Carbon $startDate, Carbon $plannedEndDate, Carbon $actualEndDate, string $completionType)
    {
        if ($completionType === 'cancellation') {
            return 0; // При отмене возврата нет
        }

        $totalDays = $startDate->diffInDays($plannedEndDate);
        $usedDays = $startDate->diffInDays($actualEndDate);

        if ($usedDays >= $totalDays) {
            return 0; // Если использовали весь период
        }

        // Рассчитываем пропорциональную стоимость использованного периода
        $usedRatio = $usedDays / $totalDays;
        $usedCost = $totalCost * $usedRatio;

        // Возвращаем разницу между оплаченной и использованной стоимостью
        $refund = $totalCost - $usedCost;

        return max(0, $refund); // Не возвращаем отрицательные значения
    }
}

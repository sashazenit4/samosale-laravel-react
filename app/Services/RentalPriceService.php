<?php

namespace App\Services;

use App\Models\Tariff;
use Carbon\Carbon;

class RentalPriceService
{
    /**
     * Расчет стоимости аренды на основе тарифа и периода
     */
    public function calculateRentalPrice(Tariff $tariff, Carbon $startDate, Carbon $endDate, int $previousDays = 0)
    {
        $days = $startDate->diffInDays($endDate);
        $totalDays = $previousDays + $days;

        // Базовая стоимость по тарифу
        $basePrice = $this->calculateBasePrice($tariff, $totalDays, $previousDays);

        // Детализированная разбивка по периодам
        $breakdown = $this->calculateBreakdown($tariff, $totalDays, $startDate, $previousDays);

        return [
            'base_price' => $basePrice,
            'total_price' => $basePrice,
            'days' => $days,
            'total_days' => $totalDays,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Детализированная разбивка стоимости по периодам
     */
    private function calculateBreakdown(Tariff $tariff, int $totalDays, Carbon $startDate = null, int $previousDays = 0): array
    {
        $breakdown = [];
        $remainingDays = $totalDays - $previousDays;
        $currentDate = $startDate ? $startDate->copy() : null;
        $currentWeek = $this->getCurrentWeek($previousDays);

        if ($totalDays <= 28) {
            while ($remainingDays > 0 && $currentWeek <= 4) {
                $weekPriceField = "price_week{$currentWeek}";
                $weekPrice = $tariff->$weekPriceField;
                $periodDays = min(7, $remainingDays);

                $description = $currentDate
                    ? "услуги проката (" . $currentDate->format('d.m') . " - " . $currentDate->copy()->addDays($periodDays)->format('d.m') . ")"
                    : "услуги проката ";

                $breakdown[] = [
                    'type' => 'week',
                    'amount' => $weekPrice,
                    'description' => $description,
                    'days' => $periodDays,
                    'week_number' => $currentWeek
                ];

                $remainingDays -= $periodDays;
                $currentWeek++;

                if ($currentDate) {
                    $currentDate->addDays($periodDays);
                }
            }
        } else {
            // Рассчитываем breakdown только для дополнительного периода (remainingDays)
            // Количество полных месяцев в дополнительном периоде
            $additionalFullMonths = floor($remainingDays / 30);
            $additionalRemainingDays = $remainingDays % 30;
            
            // Расчет дополнительных месяцев
            for ($i = 1; $i <= $additionalFullMonths; $i++) {
                $monthNumber = floor($previousDays / 30) + $i;
                $description = $currentDate
                    ? "Месяц {$monthNumber} (" . $currentDate->format('d.m') . " - " . $currentDate->copy()->addMonth()->format('d.m') . ")"
                    : "Месяц {$monthNumber}";

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
            if ($additionalRemainingDays > 0) {
                // Определяем, с какой недели начинать, учитывая позицию в месяце
                $positionInMonth = ($previousDays % 30) + ($additionalFullMonths * 30);
                $currentWeek = $this->getCurrentWeek($positionInMonth);

                while ($additionalRemainingDays > 0 && $currentWeek <= 4) {
                    $weekPriceField = "price_week{$currentWeek}";
                    $weekPrice = $tariff->$weekPriceField;
                    $periodDays = min(7, $additionalRemainingDays);

                    $description = $currentDate
                        ? "услуги проката (" . $currentDate->format('d.m') . " - " . $currentDate->copy()->addDays($periodDays)->format('d.m') . ")"
                        : "услуги проката ";

                    $breakdown[] = [
                        'type' => 'week',
                        'amount' => $weekPrice,
                        'description' => $description,
                        'days' => $periodDays,
                        'week_number' => $currentWeek
                    ];

                    $additionalRemainingDays -= $periodDays;
                    $currentWeek++;

                    if ($currentDate) {
                        $currentDate->addDays($periodDays);
                    }
                }
            }
        }

        return $breakdown;
    }

    /**
     * Расчет базовой стоимости по тарифу с учетом предыдущих дней
     */
    private function calculateBasePrice(Tariff $tariff, int $totalDays, int $previousDays = 0)
    {
        if ($totalDays <= 7) {
            return $tariff->price_week1;
        } elseif ($totalDays <= 14) {
            return $previousDays < 7
                ? $tariff->price_week1 + $tariff->price_week2
                : $tariff->price_week2;
        } elseif ($totalDays <= 21) {
            if ($previousDays < 7) {
                return $tariff->price_week1 + $tariff->price_week2 + $tariff->price_week3;
            } elseif ($previousDays < 14) {
                return $tariff->price_week2 + $tariff->price_week3;
            } else {
                return $tariff->price_week3;
            }
        } elseif ($totalDays <= 28) {
            if ($previousDays < 7) {
                return $tariff->price_week1 + $tariff->price_week2 + $tariff->price_week3 + $tariff->price_week4;
            } elseif ($previousDays < 14) {
                return $tariff->price_week2 + $tariff->price_week3 + $tariff->price_week4;
            } elseif ($previousDays < 21) {
                return $tariff->price_week3 + $tariff->price_week4;
            } else {
                return $tariff->price_week4;
            }
        } else {
            return $this->calculateMonthlyPrice($tariff, $totalDays, $previousDays);
        }
    }

    /**
     * Расчет стоимости для периодов больше 28 дней с учетом предыдущих дней
     */
    private function calculateMonthlyPrice(Tariff $tariff, int $totalDays, int $previousDays = 0)
    {
        // Количество полных месяцев
        $fullMonths = floor($totalDays / 30);
        $remainingDays = $totalDays % 30;

        $totalPrice = $fullMonths * $tariff->price_month;

        // Добавляем стоимость за оставшиеся дни по недельной логике
        if ($remainingDays > 0) {
            $previousWeeksDays = $previousDays % 30;
            $currentWeek = $this->getCurrentWeek($previousWeeksDays);

            if ($remainingDays <= 7) {
                $weekPriceField = "price_week{$currentWeek}";
                $totalPrice += $tariff->$weekPriceField;
            } elseif ($remainingDays <= 14) {
                $totalPrice += $this->getWeekPriceSum($tariff, $currentWeek, 2);
            } elseif ($remainingDays <= 21) {
                $totalPrice += $this->getWeekPriceSum($tariff, $currentWeek, 3);
            } elseif ($remainingDays <= 28) {
                $totalPrice += $this->getWeekPriceSum($tariff, $currentWeek, 4);
            } else {
                $totalPrice += $tariff->price_month;
            }
        }

        return $totalPrice;
    }

    /**
     * Получить текущую неделю на основе количества дней
     */
    private function getCurrentWeek(int $days): int
    {
        return min(4, floor(($days + 6) / 7) + 1);
    }

    /**
     * Получить сумму цен за указанное количество недель
     */
    private function getWeekPriceSum(Tariff $tariff, int $startWeek, int $weeksCount)
    {
        $sum = 0;
        for ($i = 0; $i < $weeksCount; $i++) {
            $weekNumber = min(4, $startWeek + $i);
            $weekPriceField = "price_week{$weekNumber}";
            $sum += $tariff->$weekPriceField;
        }
        return $sum;
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

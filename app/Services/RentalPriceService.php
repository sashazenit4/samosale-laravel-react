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

        return [
            'base_price' => $basePrice,
            'total_price' => $basePrice,
            'days' => $days
        ];
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
                // 1 месяц + 0-1 неделя
                $totalPrice += $tariff->price_week1;
            } elseif ($remainingDays <= 14) {
                // 1 месяц + 1-2 недели
                $totalPrice += $tariff->price_week1 + $tariff->price_week2;
            } elseif ($remainingDays <= 21) {
                // 1 месяц + 2-3 недели
                $totalPrice += $tariff->price_week1 + $tariff->price_week2 + $tariff->price_week3;
            } else {
                // 1 месяц + 3-4 недели = 2 месяца
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

        // Рассчитываем стоимость использованного периода
        $tariff = Tariff::first(); // Нужно будет передавать тариф или хранить его в аренде
        $usedCost = $this->calculateRentalPrice($tariff, $startDate, $actualEndDate)['total_price'];

        // Возвращаем разницу между оплаченной и использованной стоимостью
        return $totalCost - $usedCost;
    }
}

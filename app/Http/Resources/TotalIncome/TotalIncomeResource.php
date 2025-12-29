<?php

namespace App\Http\Resources\TotalIncome;

use Illuminate\Http\Resources\Json\JsonResource;

class TotalIncomeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'total_income'     => $this->resource['total_income'] ?? 0,
            'received_income'  => $this->resource['received_income'] ?? 0,
            'total_customers'  => $this->resource['total_customers'] ?? 0,
            'affiliate_income' => $this->resource['affiliate_income'] ?? 0,
            'total_reviews'    => $this->resource['total_reviews'] ?? 0,
            'chart_data'       => collect($this->resource['chart_data'])->map(function ($item) {
                return [
                    'date'  => $item['date'],
                    'total' => $item['total'],
                ];
            }),
        ];
    }
}

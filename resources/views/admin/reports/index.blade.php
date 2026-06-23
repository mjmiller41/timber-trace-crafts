@extends('layouts.admin')

@section('page-title', 'Reports')

@section('content')

{{-- Revenue by Month --}}
<div style="margin-bottom: 2rem;">
    <h2 style="font-family: 'Playfair Display', serif; font-size: 1.125rem; font-weight: 300; color: #333; margin-bottom: 1rem;">
        Revenue by Month
    </h2>

    <div class="admin-card" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th style="text-align: right;">Orders</th>
                        <th style="text-align: right;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenueByMonth as $row)
                    @php
                        $date = \Carbon\Carbon::createFromFormat('Y-m', $row->month);
                    @endphp
                    <tr>
                        <td style="font-weight: 500;">{{ $date->format('F Y') }}</td>
                        <td style="text-align: right; color: #6b7280;">{{ number_format($row->orders) }}</td>
                        <td style="text-align: right; font-weight: 600; color: #2C4C3B;">
                            ${{ number_format($row->revenue, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #9ca3af; padding: 2.5rem;">
                            No revenue data yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($revenueByMonth->isNotEmpty())
                <tfoot>
                    <tr style="background: #f9fafb;">
                        <td style="font-weight: 700; padding: 0.75rem 1rem;">Total (last 12 months)</td>
                        <td style="text-align: right; font-weight: 600; padding: 0.75rem 1rem;">
                            {{ number_format($revenueByMonth->sum('orders')) }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #2C4C3B; padding: 0.75rem 1rem;">
                            ${{ number_format($revenueByMonth->sum('revenue'), 2) }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Top Products --}}
<div>
    <h2 style="font-family: 'Playfair Display', serif; font-size: 1.125rem; font-weight: 300; color: #333; margin-bottom: 1rem;">
        Top Products by Revenue
    </h2>

    <div class="admin-card" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th style="text-align: right;">Units Sold</th>
                        <th style="text-align: right;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $i => $product)
                    <tr>
                        <td style="color: #9ca3af; font-size: 0.8125rem; width: 2rem;">{{ $i + 1 }}</td>
                        <td style="font-weight: 500;">{{ $product->name }}</td>
                        <td style="text-align: right; color: #6b7280;">{{ number_format($product->units_sold) }}</td>
                        <td style="text-align: right; font-weight: 600; color: #2C4C3B;">
                            ${{ number_format($product->revenue, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #9ca3af; padding: 2.5rem;">
                            No sales data yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

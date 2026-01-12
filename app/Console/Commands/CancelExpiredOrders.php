<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;

class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired';
    protected $description = 'Batalkan pesanan jika deadline pembayaran habis';

    public function handle()
    {
        $now = Carbon::now();

        $orders = Order::where('status', 'pending')
            ->where('payment_status', 'pending')
            ->whereNotNull('payment_due')
            ->where('payment_due', '<', $now)
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'unpaid'
            ]);
        }

        $this->info('Pesanan expired berhasil dibatalkan');
    }
}

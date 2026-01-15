<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl = 'https://api.fonnte.com/send';
    protected string $token = '';

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
    }

    public function send(string $target, string $message, string $countryCode = '62')
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token
            ])->post($this->apiUrl, [
                'target' => $target,
                'message' => $message,
                'countryCode' => $countryCode
            ]);

            $result = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $result
                ];
            }

            Log::error('WhatsAppService', [
                'target' => $target,
                'message' => $message,
                'countryCode' => $countryCode,
                'response' => $result
            ]);

            return [
                'success' => false,
                'error' => $result
            ];
        } catch (\Exception $th) {
            Log::error('WhatsAppService', [
                'target' => $target,
                'message' => $message,
                'countryCode' => $countryCode,
                'error' => $th->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $th->getMessage()
            ];
        }
    }

    public function sendTransactionReceipt(string $phoneNumber, array $transactionData)
    {
        $message = $this->formatTransactionReceipt($transactionData);

        return $this->send($phoneNumber, $message);
    }

    public function formatTransactionReceipt(array $data)
    {
        $storeName = config('app.name');
        $items = '';

        foreach ($data['items'] as $item) {
            $items .= "• {$item['name']} x{$item['quantity']} = Rp " . number_format($item['subtotal'], 0, ',', '.') . "\n";
        }

        return <<<MESSAGE
🧾 *STRUK PEMBELIAN*
{$storeName}

📋 *Kode Transaksi:* {$data['code']}
📅 *Tanggal:* {$data['date']}
👤 *Pelanggan:* {$data['customer_name']}

*Daftar Belanja:*
{$items}
-------------------
💰 *Subtotal:* Rp {$data['subtotal']}
📊 *Pajak (11%):* Rp {$data['tax']}
-------------------
💳 *TOTAL:* Rp {$data['total']}

Terima kasih atas pembelian Anda! 🙏
MESSAGE;
    }
}

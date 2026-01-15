<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetTransactionsRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\PaginatedResource;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TransactionController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using('view_transactions'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create_transactions'), only: ['store']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(GetTransactionsRequest $request)
    {
        $transactions = Transaction::with(['customer', 'items.product'])
            ->search($request->search)
            ->latest()
            ->paginate($request->limit ?? 10);

        return ApiResponse::success(
            new PaginatedResource($transactions, TransactionResource::class),
            'Transactions list'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $itemsData = [];
            $itemsForNotification = [];

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Product with ID {$item['product_id']} not found");
                }

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product {$product->name}");
                }

                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                    'model' => $product // Keep reference for stock update
                ];

                $itemsForNotification[] = [
                    'name' => $product->name,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];
            }

            // Calculate Tax (Assuming 11%)
            $tax = $subtotal * 0.11;
            $total = $subtotal + $tax;

            // Generate Code (TRX-TIMESTAMP-RANDOM)
            $code = 'TRX-' . time() . '-' . rand(1000, 9999);

            $transaction = Transaction::create([
                'code' => $code,
                'customer_id' => $request->customer_id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            foreach ($itemsData as $itemData) {
                $transaction->items()->create($itemData);

                // Deduct Stock
                $itemData['model']->decrement('stock', $itemData['quantity']);
            }

            DB::commit();

            $transaction->load(['customer', 'items.product']);

            if (!empty($data['send_notification']) && $transaction->customer?->phone) {
                $whatsAppService = new WhatsAppService();

                $whatsAppService->sendTransactionReceipt(
                    $transaction->customer->phone,
                    [
                        'code' => $transaction->code,
                        'date' => $transaction->created_at->format('d/m/Y H:i'),
                        'customer_name' => $transaction->customer->name,
                        'items' => $itemsForNotification,
                        'subtotal' => number_format($subtotal, 0, ',', '.'),
                        'tax' => number_format($tax, 0, ',', '.'),
                        'total' => number_format($total, 0, ',', '.'),
                    ]
                );
            }

            return ApiResponse::success(
                new TransactionResource($transaction),
                'Transaction created successfully',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaction = Transaction::with(['customer', 'items.product'])->find($id);

        if (!$transaction) {
            return ApiResponse::error(
                'Transaction not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new TransactionResource($transaction),
            'Transaction details'
        );
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        if($id)
        {
            $transaction = Transaction::with(['items.product'])->find($id);
            
            if($transaction)
            {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ada',
                    404
                );
            }
        }

        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if($status)
        {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list transaksi berhasil diambil'
        );
    }

    // public function checkout(Request $request)
    // {
        
    //     $request->validate([
    //         'items' => 'required|array',
    //         'items.*.id' => 'required|exists:products,id',
    //         'total_price' => 'required',
    //         'shipping_price' => 'required',
    //         'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED',
    //     ]);

    //     $transaction = Transaction::create([
    //         'users_id' => Auth::user()->id,
    //         'address' => $request->address,
    //         'total_price' => $request->total_price,
    //         'shipping_price' => $request->shipping_price,
    //         'status' => $request->status
    //     ]);
        
    //     foreach ($request->items as $item) {
    //         TransactionItem::create([
    //             'users_id' => Auth::user()->id,
    //             'products_id' => $item['id'],
    //             'transactions_id' => $transaction->id,
    //             'quantity' => $item['quantity'],
    //         ]);
    //     }

    //     // return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil');

    //     $transaction->load('items.product');

    //     return ResponseFormatter::success($transaction, 'Transaction successful');

    // }

    public function checkout(Request $request)
    {
        // Separate validation to check product IDs existence
        $productIdValidation = Validator::make($request->items, [
            '*.id' => 'required|exists:products,id',
        ]);

        if ($productIdValidation->fails()) {
            return ResponseFormatter::error($productIdValidation->errors(), 'Invalid product IDs', 422);
        }

        // Main validation
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required', // No need to repeat "exists:products,id" here
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED',
        ]);

        // Create the transaction
        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);

        // Create transaction items
        foreach ($request->items as $item) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'products_id' => $item['id'],
                'transactions_id' => $transaction->id,
                'quantity' => $item['quantity'],
            ]);
        }

        // Load product details for the created transaction items
        $transaction->load('items.product');

        return ResponseFormatter::success($transaction, 'Transaction successful');
    }
}


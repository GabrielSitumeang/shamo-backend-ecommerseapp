<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TransactionItem;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $query = Transaction::with(['user']);

            return DataTables::of($query)
                ->addColumn('action', function ($item) {
                    return '
                    <button class="border border-green-500 bg-green-500 text-white rounded-md px-2 py-1 m-2 transition duration-500 ease select-none hover:bg-green-600 focus:outline-none focus:shadow-outline" >
                        <a  
                            href="' . route('dashboard.transaction.show', $item->id) . '">
                            Show
                        </a>
                    </button>

                        <button class="border border-gray-500 bg-gray-500 text-white rounded-md px-2 py-1 m-2 transition duration-500 ease select-none hover:bg-gray-600 focus:outline-none focus:shadow-outline" >    
                            <a 
                                href="' . route('dashboard.transaction.edit', $item->id) . '">
                                Edit
                            </a>
                        </button> ';    
                })
                ->editColumn('total_price', function ($item) {
                    return number_format($item->total_price);
                })
                ->rawColumns(['action'])
                ->make();
        }

        return view('pages.dashboard.transaction.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        if (request()->ajax()) {
            $query = TransactionItem::with(['product'])->where('transactions_id', $transaction->id);

            return DataTables::of($query)
                ->editColumn('product.price', function ($item) {
                    return number_format($item->product->price);
                })
                ->make();
        }

        return view('pages.dashboard.transaction.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        return view('pages.dashboard.transaction.edit',[
            'item' => $transaction
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionRequest $request, Transaction $transaction)
    {
        $data = $request->all();

        $transaction->update($data);

        return redirect()->route('dashboard.transaction.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameSession;

class InvoiceController extends Controller
{

    public function show($id)
    {
        // Lấy thông tin phiên chơi + bàn + món ăn đã gọi
        $session = GameSession::with(['bidaTable', 'orderItems.product'])->findOrFail($id);

        return view('invoices.print', compact('session'));
    }

}

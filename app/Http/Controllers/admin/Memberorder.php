<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\MenuOption;
use App\Models\Orders;
use App\Models\OrdersDetails;
use App\Models\OrdersOption;
use App\Models\Table;
use App\Models\User;
use App\Models\UsersCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PromptPayQR\Builder;

class Memberorder extends Controller
{

    public function Memberorder()
    {
        $data['function_key'] = 'Memberorder';
        $data['rider'] = User::where('is_rider', 1)->get();
        $data['config'] = Config::first();
        return view('order_member.order', $data);
    }

    public function MemberorderlistData()
    {
        $data = [
            'status' => false,
            'message' => '',
            'data' => []
        ];
        $order = DB::table('orders as o')
            ->select(
                'o.table_id',
                DB::raw('SUM(o.total) as total'),
                DB::raw('MAX(o.created_at) as created_at'),
                DB::raw('MAX(o.status) as status'),
                DB::raw('MAX(o.remark) as remark')
            )
            ->whereNot('table_id')
            ->groupBy('o.table_id')
            ->orderByDesc('created_at')
            ->where('status', 1)
            ->get();

        if (count($order) > 0) {
            $info = [];
            foreach ($order as $rs) {
                if ($rs->status == 1) {
                    $status = '<button class="btn btn-sm btn-primary">ออเดอร์ใหม่</button>';
                }
                $flag_order = '<button class="btn btn-sm btn-success">สั่งหน้าร้าน</button>';
                $action = '<button data-id="' . $rs->table_id . '" type="button" class="btn btn-sm btn-outline-primary modalShow m-1">รายละเอียด</button>';
                $table = Table::find($rs->table_id);
                $info[] = [
                    'flag_order' => $flag_order,
                    'table_id' => $table->table_number,
                    'remark' => $rs->remark,
                    'status' => $status,
                    'created' => $this->DateThai($rs->created_at),
                    'action' => $action
                ];
            }
            $data = [
                'data' => $info,
                'status' => true,
                'message' => 'success'
            ];
        }
        return response()->json($data);
    }

    function DateThai($strDate)
    {
        $strYear = date("Y", strtotime($strDate)) + 543;
        $strMonth = date("n", strtotime($strDate));
        $strDay = date("j", strtotime($strDate));
        $time = date("H:i", strtotime($strDate));
        $strMonthCut = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
        $strMonthThai = $strMonthCut[$strMonth];
        return "$strDay $strMonthThai $strYear" . " " . $time;
    }

    public function MemberorderlistOrderDetail(Request $request)
    {
        $categoryId = UsersCategories::where('users_id', Session::get('user')->id)->value('categories_id');
        $orders = Orders::where('table_id', $request->input('id'))
            ->whereIn('status', [1, 2])
            ->get();
        $info = '';
        foreach ($orders as $order) {
            $info .= '<div class="mb-3">';
            $info .= '<div class="row"><div class="col d-flex align-items-end"><h5 class="text-primary mb-2">เลขออเดอร์ #: ' . $order->id . '</h5></div>
            <div class="col-auto d-flex align-items-start">';
            $info .= '</div></div>';
            $orderDetails = OrdersDetails::where('order_id', $order->id)->get()->groupBy('menu_id');
            foreach ($orderDetails as $details) {
                $menuName = optional($details->first()->menu)->name ?? 'ไม่พบชื่อเมนู';
                $orderOption = OrdersOption::where('order_detail_id', $details->first()->id)->get();
                foreach ($details as $detail) {
                    $detailsText = [];
                    if ($orderOption->isNotEmpty()) {
                        foreach ($orderOption as $key => $option) {
                            $optionName = MenuOption::find($option->option_id);
                            $detailsText[] = $optionName->type;
                        }
                        $detailsText = implode(',', $detailsText);
                    }
                    $optionType = $menuName;
                    $priceTotal = number_format($detail->price, 2);
                    $info .= '<ul class="list-group mb-1 shadow-sm rounded">';
                    $info .= '<li class="list-group-item d-flex justify-content-between align-items-start">';
                    $info .= '<div class="flex-grow-1">';
                    $info .= '<div><span class="fw-bold">' . htmlspecialchars($optionType) . '</span></div>';
                    if (!empty($detailsText)) {
                        $info .= '<div class="small text-secondary mb-1 ps-2">+ ' . $detailsText . '</div>';
                    }
                    if (!empty($detail->remark)) {
                        $info .= '<div class="small text-secondary mb-1 ps-2">+ หมายเหตุ : ' . $detail->remark . '</div>';
                    }
                    $info .= '</div>';
                    $info .= '<div class="text-end d-flex flex-column align-items-end">';
                    $info .= '<div class="mb-1">จำนวน: ' . $detail->quantity . '</div>';
                    $info .= '<div>';
                    $info .= '<button class="btn btn-sm btn-primary me-1">' . $priceTotal . ' บาท</button>';
                    $info .= '<button class="btn btn-sm btn-primary OpenRecipes" data-id="' . $detail->option_id . '">เปิดสูตรอาหาร</button></div>';
                    $info .= '</div>';
                    $info .= '</div>';
                    $info .= '</li>';
                    $info .= '</ul>';
                }
            }
            $info .= '</div>';
        }
        echo $info;
    }

    public function MemberorderRider()
    {
        $data['function_key'] = 'MemberorderRider';
        $data['rider'] = User::where('is_rider', 1)->get();
        $data['config'] = Config::first();
        return view('order_member.order_rider', $data);
    }

    public function MemberorderRiderlistData()
    {
        $data = [
            'status' => false,
            'message' => '',
            'data' => []
        ];
        $order = Orders::select('orders.*', 'users.name')
            ->join('users', 'orders.users_id', '=', 'users.id')
            ->where('table_id')
            ->whereNot('users_id')
            ->whereNot('address_id')
            ->orderBy('created_at', 'desc')
            ->get();

        if (count($order) > 0) {
            $info = [];
            foreach ($order as $rs) {
                $status = '';
                if ($rs->status == 1) {
                    $status = '<button class="btn btn-sm btn-primary">กำลังทำอาหาร</button>';
                }
                $flag_order = '<button class="btn btn-sm btn-warning">สั่งออนไลน์</button>';
                $action = '<button data-id="' . $rs->id . '" type="button" class="btn btn-sm btn-outline-primary modalShow m-1">รายละเอียด</button>';
                $info[] = [
                    'flag_order' => $flag_order,
                    'name' => $rs->name,
                    'total' => $rs->total,
                    'remark' => $rs->remark,
                    'status' => $status,
                    'created' => $this->DateThai($rs->created_at),
                    'action' => $action
                ];
            }
            $data = [
                'data' => $info,
                'status' => true,
                'message' => 'success'
            ];
        }
        return response()->json($data);
    }

    public function printOrderAdmin($id)
    {
        $config = Config::first();
        $getOrder = Orders::where('table_id', $id)->whereIn('status', [1, 2])->get();
        $order_id = array();
        $qr = '';
        $pay = 0;
        if ($config->promptpay != '') {
            $qr = Builder::staticMerchantPresentedQR($config->promptpay)->toSvgString();
            $qr = str_replace('<svg', '<svg width="150" height="150"', $qr);
            $qr = '<div style="width: 150px; height: 150px; overflow: hidden;">
                        <div style="transform: scale(1.25); transform-origin: center;">
                            ' . $qr . '
                        </div>
                    </div>';
        } elseif ($config->image_qr != '') {
            $qr = '<img width="150px" src="' . url('storage/' . $config->image_qr) . '">';
        }
        foreach ($getOrder as $rs) {
            $order_id[] = $rs->id;
            $pay = $pay + $rs->total;
        }
        $categoryId = UsersCategories::where('users_id', Session::get('user')->id)->value('categories_id');
        $order = OrdersDetails::whereIn('order_id', $order_id)
            ->with('menu', 'option')
            ->get();
        $table = Table::find($id);
        return view('printOrder', compact('config', 'order', 'qr', 'table', 'pay'));
    }

    public function printOrderAdminCook($id)
    {
        $config = Config::first();
        $getOrder = Orders::where('table_id', $id)->where('status', 1)->get();
        $order_id = array();
        $qr = '';
        $pay = 0;
        if ($config->promptpay != '') {
            $qr = Builder::staticMerchantPresentedQR($config->promptpay)->toSvgString();
            $qr = str_replace('<svg', '<svg width="150" height="150"', $qr);
            $qr = '<div style="width: 150px; height: 150px; overflow: hidden;">
                        <div style="transform: scale(1.25); transform-origin: center;">
                            ' . $qr . '
                        </div>
                    </div>';
        } elseif ($config->image_qr != '') {
            $qr = '<img width="150px" src="' . url('storage/' . $config->image_qr) . '">';
        }
        foreach ($getOrder as $rs) {
            $order_id[] = $rs->id;
            $pay = $pay + $rs->total;
        }
        $categoryId = UsersCategories::where('users_id', Session::get('user')->id)->value('categories_id');
        $order = OrdersDetails::whereIn('order_id', $order_id)
            ->with('menu', 'option')
            ->get();
        $table = Table::find($id);
        return view('printOrder', compact('config', 'order', 'qr', 'table', 'pay'));
    }

    public function printOrder($id)
    {
        $config = Config::first();
        $getOrder = Orders::where('table_id', $id)->where('status', 1)->get();
        $order_id = array();
        $qr = '';
        $pay = 0;
        if ($config->promptpay != '') {
            $qr = Builder::staticMerchantPresentedQR($config->promptpay)->toSvgString();
            $qr = str_replace('<svg', '<svg width="150" height="150"', $qr);
            $qr = '<div style="width: 150px; height: 150px; overflow: hidden;">
                        <div style="transform: scale(1.25); transform-origin: center;">
                            ' . $qr . '
                        </div>
                    </div>';
        } elseif ($config->image_qr != '') {
            $qr = '<img width="150px" src="' . url('storage/' . $config->image_qr) . '">';
        }
        foreach ($getOrder as $rs) {
            $order_id[] = $rs->id;
            $pay = $pay + $rs->total;
        }
        $categoryId = UsersCategories::where('users_id', Session::get('user')->id)->value('categories_id');
        $order = OrdersDetails::join('menus', 'orders_details.menu_id', '=', 'menus.id')
            ->whereIn('order_id', $order_id)
            ->with('menu', 'option')
            ->where('menus.categories_member_id', $categoryId)
            ->get();
        $table = Table::find($id);
        return view('printOrder', compact('config', 'order', 'qr', 'table', 'pay'));
    }

    public function printOrderRider($id)
    {
        $config = Config::first();
        $order_id = array();
        $qr = '';
        $pay = 0;
        if ($config->promptpay != '') {
            $qr = Builder::staticMerchantPresentedQR($config->promptpay)->toSvgString();
            $qr = str_replace('<svg', '<svg width="150" height="150"', $qr);
            $qr = '<div style="width: 150px; height: 150px; overflow: hidden;">
                        <div style="transform: scale(1.25); transform-origin: center;">
                            ' . $qr . '
                        </div>
                    </div>';
        } elseif ($config->image_qr != '') {
            $qr = '<img width="150px" src="' . url('storage/' . $config->image_qr) . '">';
        }
        $getOrder = Orders::where('id', $id)->where('status', 1)->get();
        foreach ($getOrder as $rs) {
            $order_id[] = $rs->id;
            $pay = $pay + $rs->total;
        }
        $categoryId = UsersCategories::where('users_id', Session::get('user')->id)->value('categories_id');
        $order = OrdersDetails::join('menus', 'orders_details.menu_id', '=', 'menus.id')
            ->whereIn('order_id', $order_id)
            ->with('menu', 'option')
            ->where('menus.categories_member_id', $categoryId)
            ->get();
        $table = Table::find($id);
        return view('printOrder', compact('config', 'order', 'qr', 'table', 'pay'));
    }


    public function listOrderDetailRider(Request $request)
    {
        $orderId = $request->input('id');
        $order = Orders::find($orderId);
        $info = '';

        if ($order) {
            $orderDetails = OrdersDetails::where('order_id', $orderId)->get()->groupBy('menu_id');
            $info .= '<div class="mb-3">';
            $info .= '<div class="row">';
            $info .= '<div class="col d-flex align-items-end"><h5 class="text-primary mb-2">เลขออเดอร์ #: ' . $orderId . '</h5></div>';
            $info .= '<div class="col-auto d-flex align-items-start">';
            $info .= '</div></div>';

            foreach ($orderDetails as $details) {
                $menuName = optional($details->first()->menu)->name ?? 'ไม่พบชื่อเมนู';
                $orderOption = OrdersOption::where('order_detail_id', $details->first()->id)->get();

                $detailsText = [];
                if ($orderOption->isNotEmpty()) {
                    foreach ($orderOption as $option) {
                        $optionName = MenuOption::find($option->option_id);
                        $detailsText[] = $optionName->type;
                    }
                }

                foreach ($details as $detail) {
                    $priceTotal = number_format($detail->price, 2);
                    $info .= '<ul class="list-group mb-1 shadow-sm rounded">';
                    $info .= '<li class="list-group-item d-flex justify-content-between align-items-start">';
                    $info .= '<div class="flex-grow-1">';
                    $info .= '<div><span class="fw-bold">' . htmlspecialchars($menuName) . '</span></div>';

                    if (!empty($detailsText)) {
                        $info .= '<div class="small text-secondary mb-1 ps-2">+ ' . implode(',', $detailsText) . '</div>';
                    }
                    if (!empty($detail->remark)) {
                        $info .= '<div class="small text-secondary mb-1 ps-2">+ หมายเหตุ : ' . $detail->remark . '</div>';
                    }
                    $info .= '</div>';
                    $info .= '<div class="text-end d-flex flex-column align-items-end">';
                    $info .= '<div class="mb-1">จำนวน: ' . $detail->quantity . '</div>';
                    $info .= '<div>';
                    $info .= '<button class="btn btn-sm btn-primary me-1">' . $priceTotal . ' บาท</button>';
                    $info .= '<button class="btn btn-sm btn-primary OpenRecipes" data-id="' . $detail->option_id . '">เปิดสูตรอาหาร</button></div>';
                    $info .= '</div>';
                    $info .= '</div>';
                    $info .= '</li>';
                    $info .= '</ul>';
                }
            }

            $info .= '</div>';
        }

        echo $info;
    }
}

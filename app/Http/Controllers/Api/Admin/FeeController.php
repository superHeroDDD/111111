<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\HotelMonthFeeSummary;
use App\Models\RatePlan;
use App\Services\ExcelService;

class FeeController extends ApiBaseController
{
    public function list(Request $request)
    {
        $month = $request->get('month');
        if (empty($month)) {
            $monthDay = now()->startOfMonth();
        } else {
            $monthDay = Carbon::parse($month)->startOfMonth();
        }
        $month = $monthDay->format('Y-m-d');
        $query = HotelMonthFeeSummary::query();
        $hotelId = $request->get('hotel_id');
        if (!empty($hotelId)) {
            $query->where('hotel_id', $hotelId);
        }
        $list = $query->with(['hotel', 'client'])->where('month', $month)->paginate(20);

        $ratePlans = RatePlan::items();
        $records = [];
        foreach ($list as $item) {
            $row = [
                'id' => $item->id,
                'hotel_id' => $item->hotel_id,
                'hotel_name' => $item->hotel->name,
                'client_id' => $item->client_id,
                'client_name' => $item->client->company_name,
                'rate_plan' => isset($ratePlans[$item->rate_plan_id]) ? $ratePlans[$item->rate_plan_id]['name'] : '',
                'monthly_fee' => $item->monthly_fee,
                'reservation_fee' => $item->reservation_fee,
                'reservation_num' => $item->reservation_num,
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
        ];
        return $this->success($data);
    }

    public function csvDownload(Request $request)
    {
        ini_set('memory_limit','1024M');
        $month = $request->get('month');
        if (empty($month)) {
            $monthDay = now()->startOfMonth();
        } else {
            $monthDay = Carbon::parse($month)->startOfMonth();
        }
        $month = $monthDay->format('Y-m-d');
        $shortMonth = $monthDay->format('Y-m');
        $query = HotelMonthFeeSummary::query();
        $hotelId = $request->get('hotel_id');
        if (!empty($hotelId)) {
            $query->where('hotel_id', $hotelId);
        }
        $list = $query->with(['hotel', 'client'])->where('month', $month)->get();
        $data = [];
        foreach ($list as $item) {
            $row = [
                'id' => $item->id,
                'hotel_id' => $item->hotel_id,
                'hotel_name' => $item->hotel->name,
                'client_id' => $item->client_id,
                'client_name' => $item->client->company_name,
                'rate_plan' => isset($ratePlans[$item->rate_plan_id]) ? $ratePlans[$item->rate_plan_id]['name'] : '',
                'monthly_fee' => $item->monthly_fee,
                'reservation_fee' => $item->reservation_fee,
                'reservation_num' => $item->reservation_num,
            ];
            $data[] = $row;
        }
        $excelService = ExcelService::instance();
        $fileName = '????????????-' . $shortMonth;
        if (!empty($hotelId)) {
            $fileName .= '-' . $hotelId;
        }

        $headData  = [
            'hotel_id' => '???????????????',
            'hotel_name' => '????????????',
            'client_id' => '??????????????????',
            'client_name' => '????????????',
            'rate_plan' => '???????????????',
            'monthly_fee' => '???????????????',
            'reservation_fee' => '???????????????',
            'reservation_num' => '??????????????????',
        ];

        return $excelService->simpleDownload($fileName, $headData, $data);
    }
}

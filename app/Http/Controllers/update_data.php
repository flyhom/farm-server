<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use \DB;

use Event;

class update_data extends BaseController
{

    public function update(Request $request)
    {
        date_default_timezone_set('Asia/Taipei');
        $sensor_arr = ['luminance', 'temp', 'humidity', 'soil_temp', 'soil_humid', 'ec', 'ph', 'atp', 'uv', 'rainfall'];
        $data = $request->all();

        if (!$data) {
            return response()->json(['status' => 400, 'msg' => "沒有傳送任何資料", 'request' => $request, 'request_data' => $data, 'request_data_content' => $request->getContent()]);
        }
        $type = $data["type"];
        $fileOriginalName = $request->file->getClientOriginalName();
        // $filename = $request->file->storeAs('data', $fileOriginalName);
        // $originalFile = $request->file('file');
        $rows= explode(PHP_EOL, Storage::get($fileOriginalName));
        dd($rows);
        foreach ($rows as $row)
        {
            $record = str_getcsv($row);
        }
        foreach ($reader as $row) {
            // Parsing the rows...
        }

        return response()->json(['status' => 200, 'msg' => "success"]);
    }
}

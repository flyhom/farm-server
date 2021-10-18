<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;
use \DB;

use Event;
use Carbon\Carbon;


class update_data extends BaseController
{

    public function update(Request $request)
    {
        date_default_timezone_set('Asia/Taipei');
        ini_set('memory_limit', -1);
        ini_set('upload_max_filesize', '512M');
        ini_set('post_max_size', '512M');
        $current_timestamp = Carbon::now()->timestamp;
        $sensor_arr = ['luminance', 'temp', 'humidity', 'soil_temp', 'soil_humid', 'ec', 'ph', 'atp', 'uv', 'rainfall'];
        $data = $request->all();

        if (!$data) {
            return response()->json(['status' => 400, 'msg' => "沒有傳送任何資料", 'request' => $request, 'request_data' => $data, 'request_data_content' => $request->getContent()]);
        }
        $type = $data["type"];
        $originalFile = $request->file('file');

        $fileOriginalName = $request->file->getClientOriginalName();

        $filepath = $request->file->storeAs('upload', $fileOriginalName);
        $rows= explode(PHP_EOL, Storage::get($filepath));
        // 資料轉array
        $arr = array();
        foreach ($rows as $row)
        {
            $record = str_getcsv($row);
            // array_push($arr, ['time' => $record[0], 'value'=> $record[1]]);
            array_push($arr, $record);
        }
        if ($arr[0][0] == 'datetime') {
            array_shift($arr);
        }
        $sql = 'INSERT INTO '. $type .' (time, value) VALUES ? ON DUPLICATE KEY UPDATE value = VALUES(value)';
        // foreach($arr as $data){
        // }
        $ans = DB::statement($sql, $arr)->toSql();
        dd($sql,$ans);
        Storage::delete($filepath);

        return response()->json(['status' => 200, 'msg' => "success"]);
    }
}

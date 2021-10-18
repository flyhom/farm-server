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
        $mode = $data["mode"];
        if (!in_array($type, $sensor_arr)) {
            return response()->json(['status' => 400, 'msg' => "目前不支援此感應器的更新，請選擇其他感應器"]);
        }
        // 檔案上傳處理
        $originalFile = $request->file('file');
        $fileOriginalName = $request->file->getClientOriginalName();
        $filepath = $request->file->storeAs('upload', $fileOriginalName);

        // 讀取檔案
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

        // 選擇SQL的執行模式
        if ($mode == 'ignore') {
            $sql = 'INSERT IGNORE INTO '. $type .' (time, value) VALUES (?, ?)';
        }elseif ($mode == 'replace') {
            $sql = 'INSERT INTO '. $type .' (time, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)';
        }
        $i = 0;
        $error = '';
        // 資料新增
        foreach($arr as $data){
            if (isset($data[0]) && isset($data[1])) {
                $ans = DB::statement($sql, [$data[0], $data[1]]);
            }else{
                // dd($i,$data);
                $error = $error . (string)($i);
            }
            $i++;
        }
        // dd($sql,$ans);

        // 上傳檔案刪除
        Storage::delete($filepath);

        if (strlen($error) > 0) {
            return response()->json(['status' => 400, 'msg' => '第 '.$error.' 筆資料更新錯誤，請檢查後重新上傳']);
        }
        return response()->json(['status' => 200, 'msg' => "success"]);
    }
}

<?php 
namespace App\Imports;

use App\Models\TrafficChart;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrafficChartImport implements ToModel, WithHeadingRow
{    
    
    public function model(array $row)
    {
        $uni_traffic_id = md5(strtolower($row['traffic_type'])  . "-" . strtolower($row['ad_type'])  . "-" .strtolower($row['country'])  . "-" .strtolower($row['device_type'])  . "-" .strtolower($row['device_os']));

        $traffic = TrafficChart::where('uni_traffic_id', $uni_traffic_id)->first();
        if (!$traffic) {
            // If user doesn't exist, create a new one
            $traffic = new TrafficChart();
            $traffic->uni_traffic_id = $uni_traffic_id;
            $traffic->traffic_type = $row['traffic_type'];
            $traffic->ad_type = $row['ad_type'];
            $traffic->country = $row['country'];
            $traffic->device_type = $row['device_type'];
            $traffic->device_os = $row['device_os'];
            $traffic->traffic = $row['traffic'];
            $traffic->avg_bid = $row['avg_bid'];
            $traffic->high_bid = $row['high_bid'];
        } else{
            $traffic->traffic_type = $row['traffic_type'];
            $traffic->ad_type = $row['ad_type'];
            $traffic->country = $row['country'];
            $traffic->device_type = $row['device_type'];
            $traffic->device_os = $row['device_os'];
            $traffic->traffic = $row['traffic'];
            $traffic->avg_bid = $row['avg_bid'];
            $traffic->high_bid = $row['high_bid'];
        }
        $traffic->save();

        return $traffic;
    }
}

<?php
namespace App\Services;

class GstService
{
    public static function calculate($amount, $rate = 18, $isInterstate = false)
    {
        $gst = ($amount * $rate) / 100;

        if ($isInterstate) {

            return [
                'cgst' => 0,
                'sgst' => 0,
                'igst' => round($gst, 2),
                'total_gst' => round($gst, 2),
            ];

        } else {

            return [
                'cgst' => round($gst / 2, 2),
                'sgst' => round($gst / 2, 2),
                'igst' => 0,
                'total_gst' => round($gst, 2),
            ];
        }
    }
}
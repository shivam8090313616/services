<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function show($dir, $img) {
      $ext = pathinfo($img);
     // ob_clean();
      $path = base_path().'/storage/app/public/'.$dir.'/'.$img;
      if($ext['extension'] == 'png') {
        header('Content-type: image/png');
      } elseif($ext['extension'] == 'jpg') {
        header('Content-type: image/jpg');
      } elseif($ext['extension'] == 'jpeg') {
        header('Content-type: image/jpeg');
      } elseif($ext['extension'] == 'webp') {
        header('Content-type: image/webp');
      }
      
     	//$path = './storage/app/public/banner-image/0b15061058f885679e06d4715d237950.png';
       readfile($path);
    }
}

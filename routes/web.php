<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompProcessController;
use App\Http\Controllers\Payment\PaymentCoinOnlineController;
// use App\Http\Controllers\Payment\AppPaymentTazapeController;
use App\Http\Controllers\Payment\PaymentPhonepeController;
use App\Http\Controllers\Payment\PaymentStripeController;
use App\Http\Controllers\Payment\PaymentTazapeController;
use App\Http\Controllers\Payment\PaymentAirpayController;
use App\Http\Controllers\Payment\PaymentPayuController;
use App\Http\Controllers\Admin\ReportAdminController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\CouponUserController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Storage;

/*  App Payment Controllers */
use App\Http\Controllers\Payment\App\AppPaymentAirpayController;
use App\Http\Controllers\Payment\App\AppPaymentPayuController;
use App\Http\Controllers\Payment\App\AppPaymentPhonepeController;
use App\Http\Controllers\Payment\App\AppPaymentStripeController;
use App\Http\Controllers\Payment\App\AppPaymentTazapeController;
use App\Http\Controllers\Payment\App\AppPaymentCoinOnlineController;
use App\Http\Controllers\Payment\App\AppPaymentRazorpayController;
use App\Http\Controllers\RedisFunctionsController;

/*  
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
  */
// clear cache route
Route::get('/clear-all', function () {
  \Artisan::call('route:clear');
  \Artisan::call('cache:clear');
  \Artisan::call('view:clear');
  \Artisan::call('config:clear');
  return 'Cache Cleared';
})->name('clear');

//** Cron Routes START **//

Route::get('/doc-test', [RedisFunctionsController::class, 'setFunctions']);
/* This route update the users wallet */
Route::get('/update/users-wallet/', [RedisFunctionsController::class, 'userWalletUpdate']);
/* This route remove ad sessions */
Route::get('/remove/ad-session/', [RedisFunctionsController::class, 'removeAdSession']);
/* This route remove daily click count */
Route::get('/remove/daily-click/', [RedisFunctionsController::class, 'removeDailyClickCount']);
/* This route insert the bulk impressions into db */
Route::get('/bulk-impression/', [RedisFunctionsController::class, 'setBulkImp']);
/* This route insert the bulk clicks into db */
Route::get('/bulk-click/', [RedisFunctionsController::class, 'setBulkClk']);

//** Cron Routes END **//

/*  Autometic Deactive Coupon */
Route::get('/today/coupon/deactive', [CouponUserController::class, 'tdaycpmdeactive']);
/*  Campaign Deduct Amount User Wallet   */
Route::get('/adclck/{camp_id}', [CompProcessController::class, 'deductAmt']);
Route::get('/systemInfo', [CompProcessController::class, 'systemInfo_device']);

Route::get('/app/update/webdata', [UserController::class, 'updateWebData']);
Route::post('/app/ads', [UserController::class, 'adsdata']);

Route::get('/image/banner-image/{id}', function ($id) {

  // Storage::disk('public')->get('banner-image/
  //  echo $data; exit;
  // echo $pat = 'sdfa'; exit;
  $ext = pathinfo($id);

  $ext = $ext['extension'];
  if ($ext == 'gif') {
    header("Content-type: image/gif");
  } elseif ($ext == 'png') {
    header("Content-type: image/png");
  } elseif ($ext == 'jpg') {
    header("Content-type: image/jpg");
  } elseif ($ext == 'jpeg') {
    header("Content-type: image/jpeg");
  } elseif ($ext == 'webp') {
    header("Content-type: image/webp");
  }
  $img = Storage::disk('public')->get('banner-image/' . $id);
  // $img = file_get_contents('https://services.7searchppc.com/image/banner-image/'.$id);
  echo $img;
});

/* ################################ Payment GetWays Section ####################### */

/* ############ Payment Getway Stripe ################## */
Route::post('payment/stripe', [PaymentStripeController::class, 'payment_stripe']);
Route::get('stripe/response/{data}', [PaymentStripeController::class, 'stripe_response']);

Route::post('app_payment_stripe', [AppPaymentStripeController::class, 'payment_stripe']);
Route::get('app_stripe_success/{data}', [AppPaymentStripeController::class, 'stripe_response']);

Route::get('razorpay/success/', [AppPaymentRazorpayController::class, 'razorpayResponsesuccess']);
Route::get('razorpay/failed', [AppPaymentRazorpayController::class, 'razorpayResponsefailed']);
/* ################ Payment Gateway Payu ##################### */
Route::post('payment/payu', [PaymentPayuController::class, 'payment_payu']);
Route::post('payment/response', [PaymentPayuController::class, 'response']);

Route::Post('app_payment_payu', [AppPaymentPayuController::class, 'payment_payu']);
Route::post('app_payment_success', [AppPaymentPayuController::class, 'response']);
Route::post('app_razorpay/failed', [AppPaymentRazorpayController::class, 'response']);

/* ################ Payment Gateway Phonepe ##################### */
Route::post('payment/phonepe', [PaymentPhonepeController::class, 'payment_phonepe']);
Route::post('phonepe/response', [PaymentPhonepeController::class, 'response']);

Route::post('app_payment_phonepe', [AppPaymentPhonepeController::class, 'payment_phonepe']);
Route::post('app_payment_response', [AppPaymentPhonepeController::class, 'response']);
Route::post('app_phonepe/failed', [AppPaymentRazorpayController::class, 'response']);

/* ############# Payment Gateway TazaPaye ############################# */

Route::post('payment/payment_tazapay', [PaymentTazapeController::class, 'payment_tazapay']);
Route::get('tazapay/response', [PaymentTazapeController::class, 'response']);

/* ############# Payment Gateway App TazaPaye ############################# */
Route::Post('app_payment_payment_tazapay', [AppPaymentTazapeController::class, 'payment_tazapay']);
Route::get('app_tazapay_response', [AppPaymentTazapeController::class, 'response']);

/* ################ Payment Gateway Airpay ##################### */
Route::any('payment/airpay', [PaymentAirpayController::class, 'payment_airpay']);
Route::post('airpay/response', [PaymentAirpayController::class, 'response']);

Route::any('app_payment_airpay', [AppPaymentAirpayController::class, 'payment_airpay']);

/* ####################### Bitcoin QR Code - Route api.php ######################  */

// Route::post('user/payment/upscreenshot',[PaymentCoinQrController::class,'upload_screen']);

/* ####################### Bitcoin online #################################### */
Route::any('payment/bitcoin/online', [PaymentCoinOnlineController::class, 'bitcoin_online']);
Route::any('payment/bitcoin/online/response', [PaymentCoinOnlineController::class, 'bitcoin_response']);
Route::any('payment/bitcoin/online/app_response', [PaymentCoinOnlineController::class, 'bitcoin_app_response']);
// Route::post('payment/coinpay/response',[PaymentCoinOnlineController::class,'bitcoin_response2']);


/* ####################### Bitcoin online #################################### */
Route::post('app_payment_bitcoin_online', [AppPaymentCoinOnlineController::class, 'bitcoin_online']);
Route::any('app_payment_bitcoin_online_response', [AppPaymentCoinOnlineController::class, 'bitcoin_response']);
//  Route::any('app_payment_bitcoin_online_response',[AppPaymentCoinOnlineController::class,'bitcoin_app_response']);


Route::get('admin/genrate/pdf/user', [ReportAdminController::class, 'pdfuser']);
/* Open Registration Section  */
Route::get('/registration', [UserController::class, 'registration']);
Route::get('/verification/user/{uid}', [UserController::class, 'verifyuser']);
Route::post('ajax/registration/add', [UserController::class, 'addrRegistration']);
Route::post('ajax/registration/cuntry-name', [UserController::class, 'getCuntryName']);
/* End Registration Section */
/* #################        Send Mail User Coupon    ###################### */
Route::get('send/coupon/get', [CouponController::class, 'couponusersendmail']);
Route::post('chagepass', [CouponController::class, 'changepass']);

Route::get('image/{dir}/{img}', [ImageController::class, 'show']);
   // Route::get('uploadcountryip', [CouponController::class, 'upload_cuntry_ip']);

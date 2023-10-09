<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;


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

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/dashboard', 'HomeController@dashboard')->name('dashboard');
//Route::get('/tree', 'HomeController@tree')->name('tree');
Route::get('/tree', 'HomeController@tree_view')->name('tree_view');
Route::get('/make_wallet', 'HomeController@make_wallet')->name('make_wallet');
Route::get('/get_wallet', 'HomeController@get_wallet')->name('get_wallet');
Route::post('/register_new_member','HomeController@register_new_member')->name('register_new_member');

Route::get('/matrix','HomeController@matrix')->name('matrix');
Route::get('/tree_run','HomeController@tree_run')->name('tree_run');
Route::get('/tree2','HomeController@tree_view')->name('tree_view');
Route::get('/tree_tabs','HomeController@tree_tabs')->name('tree_tabs');
Route::get('/reffer_friend','HomeController@reffer_friend')->name('reffer_friend');
Route::get('/packages','HomeController@packages')->name('packages');
Route::post('/matrix_get_user','HomeController@matrix_get_user');



// Setting
Route::get('/edit_details/{id}', [HomeController::class, 'edit_details']);   // Show Edit Form
Route::post('/update_details', [HomeController::class, 'update_details']);   // Update Data
Route::post('/password/update', [HomeController::class, 'updatePassword'])->name('password.update'); // Update Passwrod
Route::get('/delete_Account/{id}',[HomeController::class,'delete_Account']);  // Delete  Data

// deposit
Route::get('/deposit', [HomeController::class, 'deposit']); // View
Route::get('/handle', [HomeController::class, 'handle']); // View
Route::post('/deposit_save', [HomeController::class, 'deposit_save'])->name('deposit_save'); // View

// Withdrawal
Route::get('/withdrawal/{id}', [HomeController::class, 'withdrawal']); // View

// Profit
Route::get('/profit', [HomeController::class, 'profit']); // View

// bonus
Route::get('/bonus', [HomeController::class, 'bonus']); // View

// CashBox
Route::get('/cashbox', [HomeController::class, 'cashbox']); // View
// Cash In
Route::post('/save-credit', [HomeController::class, 'cashin'])->name('save.credit');
// Cash Out
Route::post('/save-debit', [HomeController::class, 'cashout'])->name('save.debit');






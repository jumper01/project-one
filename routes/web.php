<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Test1Controller;

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
    return view('home');
});
Route::get('overpassApi/zip-codes/{zipCode}/{radius}', [Test1Controller::class, 'getSurroundingZipCodesOverpassApi'])->name('overpass_get_zipCodes');
// not needed anymore
// Route::post('excel/fill-zip-codes', [Test1Controller::class, 'fillZipCodesFromExcel'])->name('fill_zipCodes_from_excel');

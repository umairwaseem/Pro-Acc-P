<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['namespace' => 'Api', 'middleware' => 'auth:sanctum'], function () {
    Route::get('test', function() {
        echo "tested";
    });
    
    #Category
    // Route::get('/category', 'CategoryController@List');
    // Route::post('/category', 'CategoryController@create');
    // Route::post('/category/update', 'CategoryController@update');
    // Route::post('/category/delete', 'CategoryController@delete');    
    
    #Project One Time
    Route::get('/projects', 'ProjectController@List');
    Route::get('/project/{id}', 'ProjectController@get');
    Route::post('/project', 'ProjectController@create');
    Route::post('/project/update', 'ProjectController@update');
    Route::post('/project/delete', 'ProjectController@delete');
    
    #Project Recurring
    Route::get('/projects_r', 'ProjectController@List_Recurring');
    Route::get('/project_r/{id}', 'ProjectController@get');
    Route::post('/project_r', 'ProjectController@create');
    Route::post('/project_r/update', 'ProjectController@update');
    Route::post('/project_r/delete', 'ProjectController@delete');

    #Expense
    Route::get('/expenses-accounts', 'ExpenseController@getAccountsWithSubAccounts');
    Route::get('/expenses', 'ExpenseController@List');
    Route::post('/expense', 'ExpenseController@create');
    Route::post('/expense/update', 'ExpenseController@update');
    Route::post('/expense/delete', 'ExpenseController@delete');

    #other receipt
    Route::get('/otherreceipt', 'OtherReceiptController@List');
    Route::post('/otherreceipt', 'OtherReceiptController@create');
    Route::post('/otherreceipt/update', 'OtherReceiptController@update');
    Route::post('/otherreceipt/delete', 'OtherReceiptController@delete');

    #project_expense
    Route::get('/project_expenses', 'ProjectExpenseController@List');
    Route::get('/project_expense/{id}', 'ProjectExpenseController@get');
    Route::post('/project_expense', 'ProjectExpenseController@create');
    Route::post('/project_expense/update', 'ProjectExpenseController@update');
    Route::post('/project_expense/delete', 'ProjectExpenseController@delete');

    #Account
    Route::get('/accounts', 'AccountsController@List');
    Route::get('/account_tree', 'AccountsController@ListTree');
    Route::post('/account', 'AccountsController@create');
    Route::post('/account/update', 'AccountsController@update');
    Route::post('/account/delete', 'AccountsController@delete');
    Route::get('/account_parent', 'AccountsController@GetAccounts');

    #Receipt
    Route::get('/receipts', 'ReceiptController@List');
    Route::get('/receipt/{id}', 'ReceiptController@get');
    Route::post('/receipt', 'ReceiptController@create');
    Route::post('/receipt/update', 'ReceiptController@update');
    Route::post('/receipt/delete', 'ReceiptController@delete');

    #summery
    Route::get('/summery', 'SummeryController@get');
    Route::get('/receipt_chart', 'SummeryController@getMonthlyReceipts');
    Route::get('/expense_chart', 'SummeryController@getMonthlyExpenses');
    Route::get('/receipt_project_report', 'SummeryController@getReceipts');
    Route::post('/receipt_project_report_date', 'SummeryController@getReceiptsByDate');

    # project_payment
    Route::get('/project_payments', 'ProjectPaymentsController@List');
    Route::get('/project_payment/{id}', 'ProjectPaymentsController@get');
    Route::post('/project_payment', 'ProjectPaymentsController@create');
    Route::post('/project_payment/update', 'ProjectPaymentsController@update');
    Route::post('/project_payment/delete', 'ProjectPaymentsController@delete');

    # Bank
    Route::get('/banks', 'BankController@List');
    Route::get('/bank/{id}', 'BankController@get');
    Route::post('/bank', 'BankController@create');
    Route::post('/bank/update', 'BankController@update');
    Route::post('/bank/delete', 'BankController@delete');
    
});


Route::group(['namespace' => 'Api'], function(){
	Route::post('login', 'LoginController@Login');
	Route::post('signup', 'MainController@Signup');
});

Route::get('unauthorized', function(){
	return App\Http\Helpers\Response::SimpleError('Authentication Failed!');
})->name('unauthorized');
<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\Voucher;

class AccountReportController extends Controller
{
    // constructor
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    // function to view account ledger report to view all transactions of an account with opening balance and closing balance and every day balance
    public function AccountLedgerReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        try {
            // get all vouchers of this account
            $vouchers = Voucher::where('account_id', $validatedData['account_id'])
                ->whereBetween('vr_date', [$validatedData['from_date'], $validatedData['to_date']])
                ->orderBy('vr_date', 'asc')
                ->get()
                ->toArray();

            // get opening balance of this account
            $opening_balance = Voucher::where('account_id', $validatedData['account_id'])
                ->where('vr_date', '<', $validatedData['from_date'])
                ->sum('debit') - Voucher::where('account_id', $validatedData['account_id'])
                ->where('vr_date', '<', $validatedData['from_date'])
                ->sum('credit');

            // get closing balance of this account
            $closing_balance = Voucher::where('account_id', $validatedData['account_id'])
                ->where('vr_date', '<=', $validatedData['to_date'])
                ->sum('debit') - Voucher::where('account_id', $validatedData['account_id'])
                ->where('vr_date', '<=', $validatedData['to_date'])
                ->sum('credit');

            // create a tree structure
            $tree = $this->helper->createTree($vouchers);

            // create a new array to store all transactions
            $transactions = [];

            // loop through all transactions
            foreach ($tree as $key => $value) {
                // get all transactions of this date
                $transactions[$key]['date'] = $key;
                $transactions[$key]['transactions'] = $value;
                $transactions[$key]['balance'] = $opening_balance + $this->helper->getBalance($value);
                $opening_balance = $transactions[$key]['balance'];
            }

            return R::Success('Account Ledger Report', ['opening_balance' => $opening_balance, 'closing_balance' => $closing_balance, 'transactions' => $transactions]);
        } catch (\Exception $e) {
            return R::SimpleError('Error retrieving account ledger data: ' . $e->getMessage());
        }
    }


    // function to view trial balance of all accounts sum of debit and credit of the acount should not 0
    public function TrialBalanceReport(Request $request)
    {
        // validate input data
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);
    
        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }
    
        $validatedData = $request->all();
    
        // get all accounts
        $accounts = Account::select('id', 'name', DB::raw('sum(debit) as total_debit'), DB::raw('sum(credit) as total_credit'))->with(['parent'])->groupBy('id')->orderBy('name', 'asc');
    
        // filter out accounts with zero balance
        $accounts = $accounts->havingRaw('(sum(debit) - sum(credit)) <> 0');
    
        // apply date range filter on vouchers
        $accounts = $accounts->whereHas('vouchers', function ($query) use ($validatedData) {
            $query->whereBetween('vr_date', [$validatedData['from_date'], $validatedData['to_date']]);
        });
    
        // load vouchers of each account
        $accounts = $accounts->with(['vouchers' => function ($query) use ($validatedData) {
            $query->whereBetween('vr_date', [$validatedData['from_date'], $validatedData['to_date']])->orderBy('vr_date', 'asc');
        }])->get();
    
        // create a new array to store all accounts
        $result = [];
    
        // loop through all accounts
        foreach ($accounts as $account) {
            $balance = $account->total_debit - $account->total_credit;
    
            // get parent account name if present
            $parent_account = $account->parent ? $account->parent->name : '';
    
            $result[] = [
                'id' => $account->id,
                'name' => $account->name,
                'parent' => $parent_account,
                'debit' => $account->total_debit,
                'credit' => $account->total_credit,
                'balance' => $balance,
                'vouchers' => $account->vouchers,
            ];
        }
    
        return R::Success('Trial Balance Report', $result);
    }
    
    public function incomeStatement(Request $request)
    {
        // validate the input data
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $validator->validated();

        // get all revenue accounts
        $revenueAccounts = Account::where('type', 'revenue')->get();

        // get all expenses accounts
        $expenseAccounts = Account::where('type', 'expense')->get();

        // initialize the variables
        $totalRevenue = 0;
        $totalExpenses = 0;

        // loop through all revenue accounts and calculate the total revenue
        foreach ($revenueAccounts as $revenueAccount) {
            $revenue = Voucher::where('account_id', $revenueAccount->id)
                ->whereBetween('vr_date', [$validatedData['from_date'], $validatedData['to_date']])
                ->sum('credit');
            $totalRevenue += $revenue;
        }

        // loop through all expense accounts and calculate the total expenses
        foreach ($expenseAccounts as $expenseAccount) {
            $expense = Voucher::where('account_id', $expenseAccount->id)
                ->whereBetween('vr_date', [$validatedData['from_date'], $validatedData['to_date']])
                ->sum('debit');
            $totalExpenses += $expense;
        }

        // calculate the net income
        $netIncome = $totalRevenue - $totalExpenses;

        // return the results
        return R::Success('Income Statement', [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        // get all accounts
        $accounts = Account::with(['vouchers' => function ($query) use ($validatedData) {
            $query->whereBetween('vr_date', [$validatedData['from_date'], $validatedData['to_date']]);
        }])->get();

        // filter out accounts with zero balance
        $accounts = $accounts->filter(function ($account) {
            return $account->balance() != 0;
        });

        // calculate total assets and liabilities
        $total_assets = $accounts->filter(function ($account) {
            return $account->type == 'asset';
        })->sum('balance');

        $total_liabilities = $accounts->filter(function ($account) {
            return $account->type == 'liability';
        })->sum('balance');

        // calculate equity
        $total_equity = $total_assets - $total_liabilities;

        // create balance sheet data
        $balance_sheet = [
            'assets' => $accounts->filter(function ($account) {
                return $account->type == 'asset';
            })->toArray(),
            'liabilities' => $accounts->filter(function ($account) {
                return $account->type == 'liability';
            })->toArray(),
            'equity' => [
                [
                    'name' => 'Total Equity',
                    'balance' => $total_equity,
                ],
            ],
        ];

        return R::Success('Balance Sheet Report', $balance_sheet);
    }

    
}

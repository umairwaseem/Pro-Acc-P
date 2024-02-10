<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\Account;
use App\Models\Expense;
use App\Models\ProjectExpense;
use App\Models\Receipt;
use Carbon\Carbon;

class AccountsController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        $objs = Account::get();

        return R::Success('Account', $objs);
    }

    // function to create list of accounts in tree structure
    public function ListTree()
    {
        $objs = Account::get()->toArray();

        // create a tree structure
        $tree = $this->helper->createTree($objs);

        return R::Success('Account', $tree);
    }

    // function to get all accounts except account_type not equal to Group
    public function GetAccounts()
    {
        try {
            $accounts = Account::where('account_type', '!=', 'Group')->get();
            return R::Success('Accounts', $accounts);
        } catch (\Exception $e) {
            return R::SimpleError('Error retrieving accounts: ' . $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_name' => 'required|max:50',
            'parent_account_id' => 'nullable|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $parent_account = Account::find($validatedData['parent_account_id']);

            $account = new Account();
            $account->account_name = $validatedData['account_name'];
            $account->parent_account_id = $validatedData['parent_account_id'];
            $account->account_type = $parent_account->account_type;
            $account->opening_date = Carbon::now();
            $account->save();

            DB::commit();
            return R::Success('Account added', $account);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to update Category
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:accounts,id',
            'account_name' => 'required|max:50',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'account_type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'opening_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $account = Account::findOrFail($validatedData['id']);
            $account->account_name = $validatedData['account_name'];
            $account->parent_account_id = $validatedData['parent_account_id'];
            $account->account_type = $validatedData['account_type'];
            $account->opening_date = $validatedData['opening_date'];
            $account->save();

            DB::commit();
            return R::Success('Account updated', $account);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to delete Account
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'exists:accounts,id', function ($attribute, $value, $fail) {
                // Check if ID is greater than 16
                if ($value <= 16) {
                    $fail('The :attribute must be greater than 16.');
                }

                // Check if ID is present in expense, project_expenses, or receipts tables
                if (Expense::where('account_id', $value)->exists() ||
                    ProjectExpense::where('account_id', $value)->exists() ||
                    Receipt::where('account_id', $value)->exists()) {
                    $fail('The selected :attribute cannot be deleted because it is associated with other records.');
                }
            }],
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Account::find($validatedData['id']);
            $obj->delete();

            DB::commit();
            return R::Success('Account deleted', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

}

<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\Expense;
use App\Models\Account;

class ExpenseController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        $objs = Expense::get();

        return R::Success('Expense', $objs);
    }

    // Get the expense accounts from the accounts table
    public function getAccounts()
    {
        $objs = DB::table('accounts')
            ->where('account_type', 'Expense')
            ->get();

        return R::Success('Expense Accounts', $objs);
    }

    // get the expense account by with their nested sub accounts
    public function getAccountsWithSubAccounts()
    {
        $objs = Account::where('id', 5)->get()->toArray();

        // create a tree structure
        $tree = $this->helper->createList($objs);

        return R::Success('Account', $tree);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'payment' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'exp_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Expense::create($validatedData);

            DB::commit();
            return R::Success('Expense added', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to update Expense
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:expenses,id',
            'account_id' => 'required|exists:accounts,id',
            'payment' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'exp_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Expense::find($validatedData['id']);
            $obj->update($validatedData);

            DB::commit();
            return R::Success('Expense updated', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to delete Expense
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:expenses,id',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Expense::find($validatedData['id']);
            $obj->delete();

            DB::commit();
            return R::Success('Expense deleted', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }
}

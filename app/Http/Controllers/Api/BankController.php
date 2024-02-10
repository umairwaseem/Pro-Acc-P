<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\Account;
use App\Models\Bank;

class BankController extends Controller
{


    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        $objs = Bank::get();

        return R::Success('Bank', $objs);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:50',
            'address' => 'nullable|max:50',
            'account_no' => 'nullable|max:50',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {

            // create an account first if not exists with parent_account_id = 29
            $account = Account::where('account_name', $validatedData['title'])->first();
            if (!$account) {
                $account = new Account();
                $account->account_name = $validatedData['title'];
                $account->parent_account_id = 29; // 29 is the id of Bank account
                $account->account_type = 'Asset';
                $account->save();
            }


            $bank = new Bank();
            $bank->account_id = $account->id;
            $bank->title = $validatedData['title'];
            $bank->address = $validatedData['address'];
            $bank->account_no = $validatedData['account_no'];
            $bank->save();

            DB::commit();
            return R::Success('Bank added', $bank);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:banks,id',
            'title' => 'required|max:50',
            'address' => 'nullable|max:50',
            'account_no' => 'nullable|max:50',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $bank = Bank::find($validatedData['id']);
            $bank->title = $validatedData['title'];
            $bank->address = $validatedData['address'];
            $bank->account_no = $validatedData['account_no'];
            $bank->save();

            // update account name
            $account = Account::find($bank->account_id);
            $account->account_name = $validatedData['title'];
            $account->save();

            DB::commit();
            return R::Success('Bank updated', $bank);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:banks,id',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            // delete account
            $bank = Bank::find($validatedData['id']);
            $account = Account::find($bank->account_id);
            $account->delete();

            Bank::where('id', $validatedData['id'])->delete();

            DB::commit();
            return R::Success('Bank deleted');

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }
}

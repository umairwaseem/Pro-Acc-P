<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\OtherReceipt;

class OtherReceiptController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        $objs = OtherReceipt::get();

        return R::Success('Other Receipt', $objs);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'receipt' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'rec_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = OtherReceipt::create($validatedData);

            DB::commit();
            return R::Success('Other Receipt added', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to update OtherReceipt
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:other_receipts,id',
            'account_id' => 'required|exists:accounts,id',
            'receipt' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'exp_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = OtherReceipt::find($validatedData['id']);
            $obj->update($validatedData);

            DB::commit();
            return R::Success('OtherReceipt updated', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to delete OtherReceipt
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:other_receipts,id',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = OtherReceipt::find($validatedData['id']);
            $obj->delete();

            DB::commit();
            return R::Success('Other Receipt deleted', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }
}

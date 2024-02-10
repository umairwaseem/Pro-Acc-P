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
use App\Models\ProjectPayment;

class ProjectPaymentsController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        $objs = ProjectPayment::get();

        return R::Success('Project Payment', $objs);
    }

    // function to get Receipt by project id
    public function get($id)
    {
        $obj = ProjectPayment::where('project_id',$id)
        ->orderBy('due_date', 'desc')
        ->get();

        if (!$obj) {
            return R::SimpleError('Project wise payments not found');
        }

        return R::Success('Project Payment', $obj);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:paid,unpaid',
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = new ProjectPayment();
            $obj->project_id = $validatedData['project_id'];
            $obj->amount = $validatedData['amount'];
            $obj->status = $validatedData['status'];
            $obj->due_date = $validatedData['due_date'];
            $obj->save();

            DB::commit();
            return R::Success('Project Payment added', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to update Category
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:project_payments,id',
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:paid,unpaid',
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = ProjectPayment::findOrFail($validatedData['id']);
            $obj->project_id = $validatedData['project_id'];
            $obj->amount = $validatedData['amount'];
            $obj->status = $validatedData['status'];
            $obj->due_date = $validatedData['due_date'];
            $obj->save();

            DB::commit();
            return R::Success('Project payment updated', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to delete Account
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:project_payments,id',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = ProjectPayment::find($validatedData['id']);
            $obj->delete();

            DB::commit();
            return R::Success('Project Payment deleted', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

}

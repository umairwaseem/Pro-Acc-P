<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\ProjectExpense;

class ProjectExpenseController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        $objs = ProjectExpense::get();

        return R::Success('Expense', $objs);
    }

    // function to get Receipt by project id
    public function get($id)
    {
        $obj = ProjectExpense::where('project_id',$id)->get();

        if (!$obj) {
            return R::SimpleError('Project Expense not found');
        }

        return R::Success('Project Expense', $obj);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'exp_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->except('id');

        DB::beginTransaction();

        try {
            $obj = ProjectExpense::create($validatedData);

            DB::commit();
            return R::Success('Project Expense added', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to update Expense
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:project_expenses,id',
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'exp_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->except('id');

        DB::beginTransaction();

        try {
            $obj = ProjectExpense::find($validatedData['id']);
            $obj->update($validatedData);

            DB::commit();
            return R::Success('Project Expense updated', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to delete Expense
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:project_expenses,id',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = ProjectExpense::find($validatedData['id']);
            $obj->delete();

            DB::commit();
            return R::Success('Project Expense deleted', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\Receipt;
use App\Models\Project;
use App\Models\ProjectPayment;
use Carbon\Carbon;

class ReceiptController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        $objs = Receipt::get();

        return R::Success('Receipt', $objs);
    }

    // function to get Receipt by project id
    public function get($id)
    {
        $obj = Receipt::where('project_id',$id)
        ->orderBy('rec_date', 'DESC')
        ->get();

        if (!$obj) {
            return R::SimpleError('Receipt not found');
        }

        return R::Success('Receipt', $obj);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'rec_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $project = Project::find($request->project_id);
        if($project->total_receipts >= (($project->project_type == 'ONE') ? $project->budget : $project->total_payments)){
            return R::SimpleError('Receipt amount is greater than project amount');
        }

        $validatedData = $request->except('id');

        DB::beginTransaction();

        try {
            $obj = Receipt::create($validatedData);

            DB::commit();
            return R::Success('Receipt added', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to update Receipt
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:receipts,id',
            'project_id' => 'required|exists:projects,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'rec_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $project = Project::find($request->project_id);
        $receipt = Receipt::where('project_id',$request->project_id)
        ->where('id','!=',$request->id)
        ->sum('amount');
        if(($receipt + $request->amount) > (($project->project_type == 'ONE') ? $project->budget : $project->total_payments)){
            return R::SimpleError('Receipt amount is greater than project amount');
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Receipt::find($validatedData['id']);
            $obj->update($validatedData);

            DB::commit();
            return R::Success('Receipt updated', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to delete Receipt
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:receipts,id',
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Receipt::find($validatedData['id']);
            $obj->delete();

            DB::commit();
            return R::Success('Receipt deleted', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }
}

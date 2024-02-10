<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\Receipt;
use App\Models\ProjectHistory;

class ProjectController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function List()
    {
        //$objs = Project::get();
        $objs = Project::where('project_type','ONE')        
        ->get();        

        return R::Success('Project', $objs);
    }

    public function List_Recurring()
    {
        Helper::createRecurringProjectPayments();
        $objs = Project::where('project_type','RECURRING')->get();        

        return R::Success('Project', $objs);
    }

    // function to get Project by id
    public function get($id)
    {
        $obj = Project::find($id);

        if (!$obj) {
            return R::SimpleError('Project not found');
        }

        return R::Success('Project', $obj);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            'budget' => 'required',
            'project_type' => 'in:ONE,RECURRING',
            'project_status' => 'in:ACTIVE,INACTIVE,CLOSED,COMPLETED'      
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->except('id');

        DB::beginTransaction();

        try {
            $obj = Project::create($validatedData);

            ProjectHistory::create([
                'project_id' => $obj->id,
                'name' => $obj->name,
                'description' => $obj->description,
                'budget' => $obj->budget,
                'project_type' => $obj->project_type,
                'project_status' => $obj->project_status,
                'history_details' => 'Project created',
                'created_by' => Auth::user()->id
            ]);

            DB::commit();

            Helper::createRecurringProjectPayments();
            return R::Success('Project added', $obj);

        } catch (\Exception $e) {
            return R::SimpleError($e->getMessage());
        }
    }

    // function to update Category
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:projects,id',
            'name' => 'required',
            'description' => 'nullable',
            'budget' => 'required',
            'project_type' => 'in:ONE,RECURRING',
            'project_status' => 'in:ACTIVE,INACTIVE,CLOSED,COMPLETED'   
        ]);

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Project::find($validatedData['id']);
            $obj->update($validatedData);

            ProjectHistory::create([
                'project_id' => $obj->id,
                'name' => $obj->name,
                'description' => $obj->description,
                'budget' => $obj->budget,
                'project_type' => $obj->project_type,
                'project_status' => $obj->project_status,
                'history_details' => 'Project Updated',
                'created_by' => Auth::user()->id
            ]);

            DB::commit();
            return R::Success('Project updated', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }

    // function to delete Category
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'exists:projects,id', function ($attribute, $value, $fail) {
                // Check if ID is present in project_expenses, or receipts tables
                if (ProjectExpense::where('project_id', $value)->exists() ||
                    Receipt::where('project_id', $value)->exists()) {
                    $fail('The selected :attribute cannot be deleted because it is associated with other records.');
                }
            }]
        ]);
        

        if ($validator->fails()) {
            return R::SimpleError($validator->errors()->first());
        }

        $validatedData = $request->all();

        DB::beginTransaction();

        try {
            $obj = Project::find($validatedData['id']);
            $obj->delete();

            // ProjectHistory::create([
            //     'project_id' => $obj->id,
            //     'name' => $obj->name,
            //     'description' => $obj->description,
            //     'budget' => $obj->budget,
            //     'project_type' => $obj->project_type,
            //     'project_status' => $obj->project_status,
            //     'history_details' => 'Project Deleted',
            //     'created_by' => Auth::user()->id
            // ]);

            DB::commit();
            return R::Success('Project deleted', $obj);

        } catch (\Exception $e) {
            DB::rollBack();
            return R::SimpleError($e->getMessage());
        }
    }
}

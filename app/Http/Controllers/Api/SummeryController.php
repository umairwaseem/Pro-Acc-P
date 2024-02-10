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
use App\Models\OtherReceipt;

class SummeryController extends Controller
{

    // function to get Project Count the project, Total Onetime Project balance, Total Recurring Project balance
    public function get()
    {
        $oneTimeProjects = Project::where('project_type', 'ONE')->get();
        $recurringProjects = Project::where('project_type', 'RECURRING')->get();
        $otherReceipts = OtherReceipt::all();
        
        $totalOneTimeProjectBudget = $oneTimeProjects->sum('budget');
        $totalOneTimeProjectPayments = $oneTimeProjects->sum('total_receipts');
        $totalOtherReceipts = $otherReceipts->sum('receipt');
        $oneTimeProjectBalance = $totalOneTimeProjectBudget - $totalOneTimeProjectPayments;
        
        $totalRecurringProjectBudget = $recurringProjects->sum('total_payments');
        $totalRecurringProjectPayments = $recurringProjects->sum('total_receipts');
        $recurringProjectBalance = $totalRecurringProjectBudget - $totalRecurringProjectPayments;
        
        $obj = [
            'one_time_project_count' => $oneTimeProjects->count(),
            'one_time_project_balance' => $oneTimeProjectBalance,
            'recurring_project_count' => $recurringProjects->count(),
            'recurring_project_balance' => $recurringProjectBalance,
        ];
        
        return R::Success('Project', $obj);
    }

    // function to get monthly receipts of last 12 months order by month and Year
    public function getMonthlyReceipts()
    {
        $otherReceipts = OtherReceipt::where('rec_date', '>=', date('Y-m-d', strtotime('-12 months')))
            ->orderBy('rec_date', 'ASC')
            ->select('rec_date', 'receipt as amount', 'description')
            ->get();
        
        $receipts = Receipt::where('rec_date', '>=', date('Y-m-d', strtotime('-12 months')))
            ->orderBy('rec_date', 'ASC')
            ->select('rec_date', 'amount', 'description')
            ->get();

        $combinedReceipts = $otherReceipts->union($receipts)->sortBy('rec_date');
        $monthlyReceipts = [];
        foreach ($combinedReceipts as $receipt) {
            $month = date('F, Y', strtotime($receipt->rec_date));
            if (isset($monthlyReceipts[$month])) {
                $monthlyReceipts[$month] += $receipt->amount;
            } else {
                $monthlyReceipts[$month] = $receipt->amount;
            }
        }

        
        return R::Success('Monthly Receipts', $monthlyReceipts);
    }

    // function to get monthly expenses of last 12 months order by month and Year
    public function getMonthlyExpenses()
    {
        $expenses = ProjectExpense::where('exp_date', '>=', date('Y-m-d', strtotime('-12 months')))
            ->orderBy('exp_date', 'ASC')
            ->get();
        $monthlyExpenses = [];
        foreach ($expenses as $expense) {
            $month = date('F, Y', strtotime($expense->exp_date));
            if (isset($monthlyExpenses[$month])) {
                $monthlyExpenses[$month] += $expense->amount;
            } else {
                $monthlyExpenses[$month] = $expense->amount;
            }
        }
        
        return R::Success('Monthly Expenses', $monthlyExpenses);
    }

    // function to get All Project Receipt monthly basis
    public function getProjectReceipts()
    {
        $projects = Project::where('project_type', 'ONE')->get();
        $projectReceipts = [];
        foreach ($projects as $project) {
            $receipts = Receipt::where('project_id', $project->id)
                ->where('rec_date', '>=', date('Y-m-d', strtotime('-12 months')))
                ->orderBy('rec_date', 'ASC')
                ->get();
            $monthlyReceipts = [];
            foreach ($receipts as $receipt) {
                $month = date('F, Y', strtotime($receipt->rec_date));
                if (isset($monthlyReceipts[$month])) {
                    $monthlyReceipts[$month] += $receipt->amount;
                } else {
                    $monthlyReceipts[$month] = $receipt->amount;
                }
            }
            $projectReceipts[$project->name] = $monthlyReceipts;
        }
        
        return R::Success('Project Receipts', $projectReceipts);
    }

    // function to get all Receipts in details order by date
    public function getReceipts()
    {
        $receipts = Receipt::orderBy('rec_date', 'desc')->get();
        $receipts = $receipts->map(function ($receipt) {
            $receipt->project_name = $receipt->project->name;
            return $receipt;
        });
        
        return R::Success('Receipts', $receipts);
    }

    // function to get all Receipts in details filter by selected date
    public function getReceiptsByDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return R::ValidationError($validator->errors());
        }
        
        $receipts = Receipt::where('rec_date', $request->date)->orderBy('rec_date', 'ASC')->get();
        $receipts = $receipts->map(function ($receipt) {
            $receipt->project_name = $receipt->project->name;
            return $receipt;
        });
        
        return R::Success('Receipts by date', $receipts);
    }

}
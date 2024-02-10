<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['total_receipts','total_expenses','total_payments','Completed'];

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function expenses()
    {
        return $this->hasMany(ProjectExpense::class);
    }

    // funcation to get total amount of receipts
    public function getTotalReceiptsAttribute()
    {
        return $this->receipts->sum('amount');
    }

    // funcation to get total amount of project expenses
    public function getTotalExpensesAttribute()
    {
        return $this->expenses->sum('amount');
    }

    // funcation to get project payments
    public function payments()
    {
        return $this->hasMany(ProjectPayment::class);
    }

    // funcation to get project payments total
    public function getTotalPaymentsAttribute()
    {
        return $this->payments->sum('amount');
    }

    // funcation to get total receipts - budget has an attribute value completed else not completed
    public function getCompletedAttribute()
    {
        return $this->total_receipts >= $this->budget;
    }
}

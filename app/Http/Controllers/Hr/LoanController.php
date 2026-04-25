<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\Loan;
use App\Models\Hr\LoanScheduledDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::with('employee')->latest()->paginate(12);
        $employees = Employee::where('status', 'active')->get();

        return view('hr.loans.index', compact('loans', 'employees'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
            'amount' => 'required|numeric|min:1',
            'installment_amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Loan::create([
            'employee_id' => $request->employee_id,
            'amount' => $request->amount,
            'installment_amount' => $request->installment_amount ?? 0,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json(['success' => 'Loan request submitted successfully', 'reload' => true]);
    }

    public function approve($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->update(['status' => 'approved']);

        return response()->json(['success' => 'Loan approved successfully', 'reload' => true]);
    }

    public function reject($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->update(['status' => 'rejected']);

        return response()->json(['success' => 'Loan rejected', 'reload' => true]);
    }

    public function delete($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();

        return response()->json(['success' => 'Loan deleted successfully', 'reload' => true]);
    }

    // Schedule a one-time deduction (User Request)
    public function scheduleDeduction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:hr_loans,id',
            'amount' => 'required|numeric|min:1',
            'month' => 'required|date_format:Y-m', // e.g. 2026-02
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loan = Loan::findOrFail($request->loan_id);

        // Validate amount against remaining balance
        if ($request->amount > $loan->remaining_amount) {
            return response()->json(['error' => 'Deduction amount cannot exceed remaining loan balance ('.$loan->remaining_amount.')'], 422);
        }

        LoanScheduledDeduction::create([
            'loan_id' => $loan->id,
            'amount' => $request->amount,
            'deduction_month' => $request->month,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return response()->json(['success' => 'Deduction scheduled successfully', 'reload' => true]);
    }

    public function getHistory($id)
    {
        $loan = Loan::with(['employee', 'payments', 'scheduledDeductions'])->findOrFail($id);

        return response()->json($loan);
    }
}

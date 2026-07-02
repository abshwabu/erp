<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsController extends Controller
{
    public function index()
    {
        $accounts = Account::with(['accountType', 'parent'])->orderBy('code')->get();
        $this->loadBalances($accounts);
        return response()->json($accounts);
    }

    public function tree()
    {
        $accounts = Account::with('accountType')->orderBy('code')->get();
        $this->loadBalances($accounts);
        return response()->json($this->buildTree($accounts));
    }

    protected function loadBalances($accounts)
    {
        $balances = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'l.journal_id', '=', 'j.id')
            ->where('j.status', 'posted')
            ->select('l.account_id', DB::raw('SUM(l.debit_cents) as debit'), DB::raw('SUM(l.credit_cents) as credit'))
            ->groupBy('l.account_id')
            ->get()
            ->keyBy('account_id');

        foreach ($accounts as $account) {
            $bal = $balances->get($account->id);
            $debit = $bal ? (int)$bal->debit : 0;
            $credit = $bal ? (int)$bal->credit : 0;
            
            $normal = $account->accountType?->normal_balance ?? 'debit';
            if ($normal === 'debit') {
                $account->current_period_balance = $debit - $credit;
            } else {
                $account->current_period_balance = $credit - $debit;
            }
        }
    }

    protected function buildTree($accounts, $parentId = null)
    {
        $branch = [];
        foreach ($accounts as $account) {
            if ($account->parent_id == $parentId) {
                $children = $this->buildTree($accounts, $account->id);
                if ($children) {
                    $account->children = $children;
                }
                $branch[] = $account;
            }
        }
        return $branch;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:accounting_accounts,code',
            'account_type_id' => 'required|uuid|exists:accounting_account_types,id',
            'parent_id' => 'nullable|uuid|exists:accounting_accounts,id',
            'description' => 'nullable|string',
            'currency_code' => 'required|string|size:3',
            'is_active' => 'boolean',
        ]);

        $account = Account::create($validated);
        return response()->json($account, 201);
    }

    public function show($id)
    {
        $account = Account::with(['accountType', 'parent', 'children'])->findOrFail($id);
        return response()->json($account);
    }

    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:20|unique:accounting_accounts,code,' . $id,
            'account_type_id' => 'sometimes|required|uuid|exists:accounting_account_types,id',
            'parent_id' => 'nullable|uuid|exists:accounting_accounts,id',
            'description' => 'nullable|string',
            'currency_code' => 'sometimes|required|string|size:3',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);
        return response()->json($account);
    }

    public function destroy($id)
    {
        $account = Account::findOrFail($id);
        
        if ($account->is_system_account) {
            return response()->json(['message' => 'System accounts cannot be deleted.'], 403);
        }

        $account->delete();
        return response()->json(null, 204);
    }

    public function importCsv(Request $request)
    {
        // Placeholder for CSV import logic
        return response()->json(['message' => 'CSV import not implemented yet.'], 501);
    }

    public function accountTypes()
    {
        $types = AccountType::orderBy('sort_order')->get();
        return response()->json($types);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Journal;
use App\Services\Accounting\JournalService;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $query = Journal::with(['period', 'lines.account'])
            ->orderBy('journal_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }

        $journals = $query->paginate($request->get('per_page', 15));
        return response()->json($journals);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:100',
            'description' => 'required|string',
            'journal_date' => 'required|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|uuid|exists:accounting_accounts,id',
            'lines.*.debit_cents' => 'nullable|integer|min:0',
            'lines.*.credit_cents' => 'nullable|integer|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        try {
            $journal = $this->journalService.createJournal($validated);
            return response()->json($journal, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $journal = Journal::with(['period', 'lines.account', 'postedBy', 'reversalOf'])->findOrFail($id);
        return response()->json($journal);
    }

    public function post($id)
    {
        try {
            $this->journalService.postJournal($id);
            return response()->json(['message' => 'Journal posted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reverse(Request $request, $id)
    {
        $request->validate(['date' => 'required|date']);

        try {
            $reversal = $this->journalService.reverseJournal($id, $request->date);
            return response()->json($reversal, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function lines($id)
    {
        $journal = Journal::findOrFail($id);
        return response()->json($journal->lines()->with('account')->get());
    }
}

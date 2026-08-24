<?php

declare(strict_types=1);

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Support\Models\SupportTicket;
use App\Modules\Support\Models\SupportTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::with('assignee:id,name,email')
            ->withCount('messages')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tickets = $query->paginate(25);

        return $this->successResponse($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject'       => ['required', 'string', 'max:255'],
            'message'       => ['required', 'string'],
            'customer_id'   => ['nullable', 'uuid'],
            'assigned_to'   => ['nullable', 'uuid', 'exists:users,id'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_name'  => ['nullable', 'string', 'max:255'],
            'priority'      => ['sometimes', 'in:low,normal,high,urgent'],
            'channel'       => ['sometimes', 'in:web,email,phone,portal'],
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => SupportTicket::nextNumber(),
            'subject'       => $data['subject'],
            'customer_id'   => $data['customer_id'] ?? null,
            'assigned_to'   => $data['assigned_to'] ?? null,
            'contact_email' => $data['contact_email'] ?? $request->user()?->email,
            'contact_name'  => $data['contact_name'] ?? $request->user()?->name,
            'status'        => 'open',
            'priority'      => $data['priority'] ?? 'normal',
            'channel'       => $data['channel'] ?? 'web',
        ]);

        SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $request->user()?->id,
            'sender_name' => $request->user()?->name ?? 'Customer',
            'sender_type' => 'customer',
            'message'     => $data['message'],
            'is_internal' => false,
        ]);

        return $this->createdResponse($ticket->load(['assignee:id,name', 'messages']));
    }

    public function show(string $id): JsonResponse
    {
        $ticket = SupportTicket::with(['assignee:id,name,email', 'messages.user:id,name'])
            ->findOrFail($id);

        return $this->successResponse($ticket);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $data = $request->validate([
            'status'      => ['sometimes', 'in:open,in_progress,pending,resolved,closed'],
            'priority'    => ['sometimes', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        if (isset($data['status']) && in_array($data['status'], ['resolved', 'closed'], true) && !$ticket->resolved_at) {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);

        return $this->successResponse($ticket);
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $data = $request->validate([
            'message'     => ['required', 'string'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $message = SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $request->user()?->id,
            'sender_name' => $request->user()?->name ?? 'Agent',
            'sender_type' => 'agent',
            'message'     => $data['message'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return $this->createdResponse($message);
    }

    public function destroy(string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->delete();

        return $this->noContentResponse();
    }
}

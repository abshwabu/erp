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
        $query = SupportTicket::with(['assignee:id,name,email', 'customer:id,name,company'])
            ->withCount('messages')
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('channel') && $request->input('channel') !== 'all') {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', $search)
                  ->orWhere('ticket_number', 'like', $search)
                  ->orWhere('contact_name', 'like', $search)
                  ->orWhere('contact_email', 'like', $search);
            });
        }

        $tickets = $query->get();

        return $this->successResponse($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject'       => ['required', 'string', 'max:255'],
            'message'       => ['required', 'string'],
            'customer_id'   => ['nullable', 'uuid', 'exists:customers,id'],
            'assigned_to'   => ['nullable', 'uuid', 'exists:users,id'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_name'  => ['nullable', 'string', 'max:255'],
            'priority'      => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'channel'       => ['nullable', 'string', 'in:web,email,phone,portal'],
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
            'sender_name' => $data['contact_name'] ?? $request->user()?->name ?? 'Customer',
            'sender_type' => 'customer',
            'message'     => $data['message'],
            'is_internal' => false,
        ]);

        return $this->createdResponse($ticket->load(['assignee:id,name', 'customer:id,name,company', 'messages']));
    }

    public function show(string $id): JsonResponse
    {
        $ticket = SupportTicket::with([
            'assignee:id,name,email',
            'customer:id,name,company,email,phone',
            'messages.user:id,name,email',
        ])->findOrFail($id);

        return $this->successResponse($ticket);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $data = $request->validate([
            'status'      => ['sometimes', 'required', 'in:open,in_progress,pending,resolved,closed'],
            'priority'    => ['sometimes', 'required', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'subject'     => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        if (isset($data['status'])) {
            if (in_array($data['status'], ['resolved', 'closed'], true) && !$ticket->resolved_at) {
                $data['resolved_at'] = now();
            } elseif (!in_array($data['status'], ['resolved', 'closed'], true)) {
                $data['resolved_at'] = null;
            }
        }

        $ticket->update($data);

        return $this->successResponse($ticket->fresh(['assignee:id,name,email', 'customer:id,name,company']));
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $data = $request->validate([
            'message'     => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
            'sender_type' => ['nullable', 'string', 'in:agent,customer,system'],
        ]);

        $senderType = $data['sender_type'] ?? 'agent';
        $senderName = $request->user()?->name ?? ($senderType === 'agent' ? 'Support Agent' : 'Customer');

        $message = SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $request->user()?->id,
            'sender_name' => $senderName,
            'sender_type' => $senderType,
            'message'     => $data['message'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        if ($ticket->status === 'open' && !($data['is_internal'] ?? false)) {
            $ticket->update(['status' => 'in_progress']);
        }

        return $this->createdResponse($message->load('user:id,name,email'));
    }

    public function destroy(string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->delete();

        return $this->successResponse(['message' => 'Ticket deleted successfully.']);
    }
}

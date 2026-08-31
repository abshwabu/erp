<?php

declare(strict_types=1);

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Support\Models\SupportKnowledgeArticle;
use App\Modules\Support\Models\SupportTicket;
use Illuminate\Http\JsonResponse;

class SupportDashboardController extends BaseController
{
    public function stats(): JsonResponse
    {
        $totalTickets = SupportTicket::count();
        $openTickets = SupportTicket::where('status', 'open')->count();
        $inProgressTickets = SupportTicket::where('status', 'in_progress')->count();
        $pendingTickets = SupportTicket::where('status', 'pending')->count();
        $resolvedTickets = SupportTicket::whereIn('status', ['resolved', 'closed'])->count();

        $urgentTickets = SupportTicket::where('priority', 'urgent')
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $resolutionRate = $totalTickets > 0 ? (int) round(($resolvedTickets / $totalTickets) * 100) : 0;

        $ticketsByChannel = [
            'web' => SupportTicket::where('channel', 'web')->count(),
            'email' => SupportTicket::where('channel', 'email')->count(),
            'phone' => SupportTicket::where('channel', 'phone')->count(),
            'portal' => SupportTicket::where('channel', 'portal')->count(),
        ];

        $ticketsByPriority = [
            'low' => SupportTicket::where('priority', 'low')->count(),
            'normal' => SupportTicket::where('priority', 'normal')->count(),
            'high' => SupportTicket::where('priority', 'high')->count(),
            'urgent' => SupportTicket::where('priority', 'urgent')->count(),
        ];

        $recentTickets = SupportTicket::with(['assignee:id,name,email', 'customer:id,name,company'])
            ->withCount('messages')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $totalArticles = SupportKnowledgeArticle::where('is_published', true)->count();

        return $this->successResponse([
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'in_progress_tickets' => $inProgressTickets,
            'pending_tickets' => $pendingTickets,
            'resolved_tickets' => $resolvedTickets,
            'urgent_tickets' => $urgentTickets,
            'resolution_rate' => $resolutionRate,
            'tickets_by_channel' => $ticketsByChannel,
            'tickets_by_priority' => $ticketsByPriority,
            'recent_tickets' => $recentTickets,
            'total_articles' => $totalArticles,
        ]);
    }
}

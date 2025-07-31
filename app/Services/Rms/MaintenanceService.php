<?php

namespace App\Services\Rms;

use App\Models\RmMaintenanceTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class MaintenanceService
{
    public function list($userId, array $filters = [])
    {
        $query = RmMaintenanceTicket::where('user_id', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['rental_id'])) {
            $query->where('rental_id', $filters['rental_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('scheduled_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('scheduled_date', '<=', $filters['to']);
        }

        return $query->orderBy('scheduled_date', 'asc')->get();
    }

    public function create(array $data, $userId)
    {
        $data['user_id'] = $userId;
        $data['status'] = 'open';
        $data['attachments_count'] = 0;

        return RmMaintenanceTicket::create($data);
    }

    public function find($userId, $id)
    {
        return RmMaintenanceTicket::where('user_id', $userId)->findOrFail($id);
    }

    public function update($id, array $data, $userId)
    {
        $ticket = $this->find($userId, $id);
        $ticket->update($data);
        return $ticket;
    }

    public function changeStatus($id, $status, $userId)
    {
        $ticket = $this->find($userId, $id);
        $ticket->status = $status;
        $ticket->save();
        return $ticket;
    }

    public function delete($id, $userId)
    {
        $ticket = $this->find($userId, $id);
        $ticket->delete();
    }
}

<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function view(User $user, Reservation $reservation): bool
    {
        return (int) $user->id === (int) $reservation->tenant_id;
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return (int) $user->id === (int) $reservation->tenant_id;
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return (int) $user->id === (int) $reservation->tenant_id;
    }
}



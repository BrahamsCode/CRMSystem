<?php

namespace App\Services\Reservations;

/** El empleado no tiene franja (受付日) ese día: el legacy cortaba con die() sin registrar nada */
class ReservationSlotMissingException extends \RuntimeException {}

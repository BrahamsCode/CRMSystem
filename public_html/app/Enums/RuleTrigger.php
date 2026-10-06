<?php

namespace App\Enums;

/**
 * Qué dispara un envío automático.
 *
 * Reúne las tres pantallas del legacy: seguimiento (フォロー, 1–5),
 * programados (自動, 6–7) y recordatorio de reservas (リマインダー, 8).
 */
enum RuleTrigger: int
{
    case AfterVisit = 1;   // 来店後のフォロー
    case AfterSignup = 2;   // 会員登録日から○日後
    case BeforeBirthday = 3;   // 誕生日から○日前
    case VisitCycle = 4;   // 最終来店日から来店周期日数後
    case Anniversary = 5;   // 結婚記念日から○日前後
    case Dates = 6;   // 指定日
    case Weekdays = 7;   // 毎曜日
    case BeforeReservation = 8;   // リマインダー

    public function label(): string
    {
        return match ($this) {
            self::AfterVisit => 'Días después de la última visita',
            self::AfterSignup => 'Días después del alta',
            self::BeforeBirthday => 'Días antes del cumpleaños',
            self::VisitCycle => 'Al cumplirse su ciclo de visita',
            self::Anniversary => 'Aniversario de boda',
            self::Dates => 'Meses y días concretos',
            self::Weekdays => 'Días de la semana',
            self::BeforeReservation => 'Días antes de una reserva',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AfterVisit => 'A quienes llevan N días sin venir desde su última visita.',
            self::AfterSignup => 'A quienes se dieron de alta hace N días.',
            self::BeforeBirthday => 'N días antes del cumpleaños de cada cliente.',
            self::VisitCycle => 'Cuando pasa su ciclo medio de visita (más N días de margen) desde la última visita.',
            self::Anniversary => 'N días antes o después del aniversario de boda.',
            self::Dates => 'A todos los clientes que cumplan los filtros, en los meses y días elegidos.',
            self::Weekdays => 'A todos los clientes que cumplan los filtros, cada semana en los días elegidos.',
            self::BeforeReservation => 'N días antes de cada reserva. Necesita el módulo de reservas.',
        };
    }

    /** Grupo en el que se muestra en la pantalla */
    public function group(): string
    {
        return match ($this) {
            self::Dates, self::Weekdays => 'Programados',
            self::BeforeReservation => 'Recordatorios',
            default => 'Seguimiento',
        };
    }

    public function usesDays(): bool
    {
        return ! in_array($this, [self::Dates, self::Weekdays], true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::AfterVisit, self::VisitCycle => 'visit',
            self::AfterSignup => 'user-plus',
            self::BeforeBirthday => 'gift',
            self::Anniversary => 'heart',
            self::Dates, self::Weekdays => 'cal',
            self::BeforeReservation => 'clock',
        };
    }
}

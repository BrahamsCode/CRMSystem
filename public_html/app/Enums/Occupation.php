<?php

namespace App\Enums;

/**
 * Ocupación (職業). Catálogo cerrado del legacy, que lo guarda como texto
 * japonés; aquí va como smallint según el estándar §4.
 *
 * El comentario de cada caso es el valor original, necesario para migrar datos.
 */
enum Occupation: int
{
    case Empleado = 1;        // 会社員
    case Autonomo = 2;        // 自営・自由業
    case Funcionario = 3;     // 公務員
    case OtroEstudiante = 4;  // その他学生
    case AmaDeCasa = 5;       // 専業主婦
    case Temporal = 6;        // フリーター
    case SinEmpleo = 7;       // 無職
    case Otros = 8;           // その他
    case Universitario = 9;   // 大学生
    case Bachillerato = 10;   // 高校生
    case SecundariaOMenos = 11; // 中学生以下

    public function label(): string
    {
        return match ($this) {
            self::Empleado => 'Empleado',
            self::Autonomo => 'Autónomo o profesional liberal',
            self::Funcionario => 'Funcionario',
            self::OtroEstudiante => 'Otro estudiante',
            self::AmaDeCasa => 'Trabajo en el hogar',
            self::Temporal => 'Trabajo temporal',
            self::SinEmpleo => 'Sin empleo',
            self::Universitario => 'Universitario',
            self::Bachillerato => 'Bachillerato',
            self::SecundariaOMenos => 'Secundaria o menos',
            self::Otros => 'Otros',
        };
    }
}

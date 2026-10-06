<?php

namespace App\Enums;

/**
 * Para qué sirve el cupón (使用区分).
 *
 * Decide cómo llega al cliente: adjunto a un mensaje, canjeado por sellos o
 * puntos, o entregado solo al darse de alta, responder una encuesta o referir.
 */
enum CouponUsage: int
{
    case Newsletter = 1;   // メルマガ配信用
    case StampExchange = 2;   // マイページスタンプ交換用
    case SurveyReward = 3;   // アンケート回答時プレゼント用
    case Signup = 4;   // 会員登録クーポン
    case Referral = 5;   // お友達紹介時プレゼント用
    case PointExchange = 6;   // ポイント交換用

    public function label(): string
    {
        return match ($this) {
            self::Newsletter => 'Newsletter',
            self::StampExchange => 'Canje por sellos',
            self::SurveyReward => 'Premio de encuesta',
            self::Signup => 'Alta de socio',
            self::Referral => 'Referir a un amigo',
            self::PointExchange => 'Canje por puntos',
        };
    }

    /** Los canjes cuestan sellos o puntos; el resto se regala */
    public function hasCost(): bool
    {
        return in_array($this, [self::StampExchange, self::PointExchange], true);
    }

    public function costUnit(): ?string
    {
        return match ($this) {
            self::StampExchange => 'sellos',
            self::PointExchange => 'puntos',
            default => null,
        };
    }
}

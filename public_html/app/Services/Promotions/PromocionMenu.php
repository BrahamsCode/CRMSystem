<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;

/**
 * Textos del menú lateral de 販売促進管理 (left_navi3.php) que cambian con la app: getMailmagazineName()
 * da `お知らせ` con admin_master.application_use_flg = 1 y `メール` sin ella. Métodos estáticos sin argumentos
 * para `label_from` / `title_from` de config/menu/promocion.php (NUCLEO §6).
 */
class PromocionMenu
{
    public static function appEnabled(): bool
    {
        return (string) AdminMaster::current()?->application_use_flg === '1';
    }

    /** $tool_name['mail_tool_name'] */
    public static function tool(): string
    {
        return self::appEnabled() ? 'お知らせ' : 'メール';
    }

    public static function mailTitle(): string
    {
        return self::appEnabled() ? 'お知らせ配信管理' : 'メルマガ管理';
    }

    public static function mailNew(): string
    {
        return '新規'.self::tool().'配信';
    }

    public static function mailFuture(): string
    {
        return '予定配信'.self::tool().'一覧';
    }

    public static function mailPast(): string
    {
        return '過去配信'.self::tool().'一覧';
    }

    public static function testAddress(): string
    {
        return 'テスト'.self::tool().'アドレス管理';
    }

    public static function followTitle(): string
    {
        return 'フォロー'.self::tool().'管理';
    }

    public static function followNew(): string
    {
        return 'フォロー'.self::tool().'新規作成';
    }

    public static function followList(): string
    {
        return 'フォロー'.self::tool().'設定一覧';
    }

    public static function autoTitle(): string
    {
        return '自動'.self::tool().'管理';
    }

    public static function autoNew(): string
    {
        return '自動'.self::tool().'新規作成';
    }

    public static function autoList(): string
    {
        return '自動'.self::tool().'設定一覧';
    }

    public static function reminderTitle(): string
    {
        return 'リマインダ'.self::tool().'管理';
    }

    public static function reminderNew(): string
    {
        return 'リマインダ'.self::tool().'新規作成';
    }

    public static function reminderList(): string
    {
        return 'リマインダ'.self::tool().'設定一覧';
    }
}

<?php

namespace App\Support;

/**
 * Validaciones de las pantallas 確認 del legacy (`system/inc/error_check.php`), con las mismas reglas
 * y los mismos mensajes. En el legacy cada regla tenía tres funciones: `is_x()` (marca $error),
 * `is_x_p()` (imprime ` class="Ng"` en la celda) e `is_x_c()` (imprime el mensaje). Aquí:
 *
 *   $check = new LegacyCheck;
 *   $check->katakana('kaishamei_kana', $data['kaishamei_kana'])->blank('kaishamei', $data['kaishamei']);
 *   $check->fails();                       // = ($error == 1)
 *   <td colspan="3"{!! $check->ng('kaishamei') !!}> … {!! $check->messages('kaishamei') !!}</td>
 *
 * `add($campo, $mensaje)` agrega errores propios de la pantalla (duplicados, fechas…).
 */
class LegacyCheck
{
    public const MAIL_REGEX = "/^[_a-z0-9-\.\+\/\?\=]+(\.[_a-z0-9-\.\+\/\?\=]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i";

    /** @var array<string, list<string>> mensajes HTML por campo */
    private array $messages = [];

    /** @var array<string, bool> campos marcados con class="Ng" */
    private array $ng = [];

    private bool $failed = false;

    public function fails(): bool
    {
        return $this->failed;
    }

    /** ` class="Ng"` si el campo tiene error (is_x_p) */
    public function ng(string $field): string
    {
        return isset($this->ng[$field]) ? ' class="Ng"' : '';
    }

    /** Mensajes del campo, en HTML, como los imprimía is_x_c */
    public function messages(string $field): string
    {
        return implode('', $this->messages[$field] ?? []);
    }

    /** Error propio de la pantalla; $html va tal cual (p. ej. '<p class="m0">※既に…</p>') */
    public function add(string $field, string $html = '', bool $markNg = true): static
    {
        $this->failed = true;
        if ($markNg) {
            $this->ng[$field] = true;
        }
        if ($html !== '') {
            $this->messages[$field][] = $html;
        }

        return $this;
    }

    /** Marca el error (como is_x) sin class="Ng" ni mensaje: reglas que el legacy validaba sin mostrar */
    public function failSilently(): static
    {
        $this->failed = true;

        return $this;
    }

    // ── Reglas de error_check.php ────────────────────────────────────────────

    /** kuhaku_check: obligatorio (se recortan espacios 半角/全角) */
    public function blank(string $field, mixed $value): static
    {
        return trim(trim((string) $value), '　') === '' ? $this->add($field, '<p class="m0">※必須入力項目です。</p>') : $this;
    }

    /** is_num: solo dígitos (vacío permitido) */
    public function num(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('/^[0-9]+$/', (string) $value) ? $this->add($field, '<p class="m0">※半角数字で入力して下さい。</p>') : $this;
    }

    /** is_alnum_h: alfanumérico obligatorio */
    public function alnumRequired(string $field, mixed $value): static
    {
        if ((string) $value === '') {
            $this->add($field, '<p class="m0">※必須入力項目です。</p>');
        }

        return self::filled($value) && ! preg_match('/^[a-zA-Z0-9]+$/', (string) $value) ? $this->add($field, '<p class="m0">※半角英数字で入力して下さい。</p>') : $this;
    }

    /** is_alnum: alfanumérico (vacío permitido) */
    public function alnum(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('/^[a-zA-Z0-9]+$/', (string) $value) ? $this->add($field, '<p class="m0">※半角英数字で入力して下さい。</p>') : $this;
    }

    /** is_alnumhyphen: alfanumérico con guion (vacío permitido) */
    public function alnumHyphen(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('/^[a-zA-Z0-9\-]+$/', (string) $value) ? $this->add($field, '<p class="m0">※半角英数字で入力して下さい。</p>') : $this;
    }

    /** is_katakana: カタカナ, dígitos, guion, paréntesis y espacios (vacío permitido) */
    public function katakana(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('/^[ァ-ヶー0-9\-\(\)\s　]+$/u', (string) $value) ? $this->add($field, '<p class="m0">※全角カタカナで入力して下さい。</p>') : $this;
    }

    /** is_katakanaalnum: カタカナ o alfanumérico (vacío permitido) */
    public function katakanaAlnum(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('/^[ァ-ヶーa-zA-Z0-9]+$/u', (string) $value) ? $this->add($field, '<p class="m0">※全角カタカナか半角英数字で入力して下さい。</p>') : $this;
    }

    /** is_yubin: 7 dígitos (vacío permitido); $text = nombre del campo en el mensaje */
    public function zip(string $field, mixed $value, string $text = ''): static
    {
        $value = (string) $value;
        if ($value === '') {
            return $this;
        }
        if (! preg_match('/^[0-9]+$/', $value) && mb_strlen($value) !== 7) {
            $this->add($field, '※' . $text . 'は半角数字で入力して下さい。<br />');
        }

        return mb_strlen($value) !== 7 ? $this->add($field, '※' . $text . 'は半角数字7文字で入力して下さい。<br />') : $this;
    }

    /** is_mail_h: correo obligatorio */
    public function mailRequired(string $field, mixed $value): static
    {
        if ((string) $value === '') {
            $this->add($field, '<p class="m0">※メールアドレスは必須入力項目です。</p>');
        }

        return self::filled($value) && ! preg_match(self::MAIL_REGEX, (string) $value) ? $this->add($field, '<p class="m0">※メールアドレスが不適切な形式です。</p>') : $this;
    }

    /** is_mail: correo (vacío permitido) */
    public function mail(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match(self::MAIL_REGEX, (string) $value) ? $this->add($field, '<p class="m0">※メールアドレスが不適切な形式です。</p>') : $this;
    }

    /** is_date2: aaaa/mm/dd flexible (vacío permitido) */
    public function date2(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('|^\d{0,4}/\d{0,2}/\d{0,2}$|', (string) $value) ? $this->add($field, '<p class="m0">※入力内容が不適切な形式です。</p>') : $this;
    }

    /** is_date: aaaa/mm/dd estricto (vacío permitido) */
    public function date(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('|^\d{4}/\d{2}/\d{2}$|', (string) $value) ? $this->add($field, '<p class="m0">※入力内容が不適切な形式です。</p>') : $this;
    }

    /** is_url (vacío permitido) */
    public function url(string $field, mixed $value): static
    {
        return self::filled($value) && ! preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', (string) $value) ? $this->add($field, '<p class="m0">※入力内容が不適切な形式です。</p>') : $this;
    }

    // ── Conversiones de error_check.php / function.php ───────────────────────

    /** replace_tel: quita -, ー, *, / */
    public static function replaceTel(mixed $value): ?string
    {
        $value = (string) $value;

        return $value === '' ? null : str_replace(['-', 'ー', '*', '/'], '', $value);
    }

    /** "Verdadero" como en PHP (`if ($x)`): '' y '0' cuentan como vacío */
    private static function filled(mixed $value): bool
    {
        return (bool) $value;
    }
}

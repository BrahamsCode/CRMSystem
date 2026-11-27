<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;
use Illuminate\Support\Facades\DB;

/**
 * Arma el texto de las お知らせメール como system/class/cMailsoushin.php:
 * remitente (tb_office o tb_shop del 登録者), pie por defecto (admin_master.mail_footer),
 * sustitución de etiquetas por cliente (replaceMessage), enlace a Mi página (addSimpleLogin)
 * y ajuste de la デコメール (decomailTagReplace.php + imágenes a cid:).
 *
 * Uso: `$renderer = app(MailRenderer::class)->forSender($mail->登録者);` y luego por cliente `replace()`.
 */
class MailRenderer
{
    /** tb_office (cd 1) o tb_shop del 登録者: nombre del remitente y datos del pie */
    private array $sender = [];

    private array $adminMaster = [];

    /** {単語} de los campos de cliente marcados con mail_word_flg: [tb_search_form.cd => nombre] */
    private array $wordList = [];

    private array $kingakuRank = [];

    private array $raitenRank = [];

    public function __construct(private readonly SystemDomain $domain) {}

    /** setKihonData(): '' o '-1' = empresa (tb_office), si no la tienda */
    public function forSender(?string $tourokusya): static
    {
        $tourokusya = trim((string) $tourokusya);
        $row = ($tourokusya === '' || $tourokusya === '-1' || ! ctype_digit($tourokusya))
            ? DB::table('tb_office')->where('cd', 1)->first()
            : DB::table('tb_shop')->where('cd', (int) $tourokusya)->first();

        $this->sender = $row ? (array) $row : [];
        $this->adminMaster = AdminMaster::current()->getAttributes();
        $this->wordList = $this->loadWordList();
        $this->raitenRank = DB::table('tb_raiten_rank_master')->where('disp_flg', '1')->orderBy('cd')->pluck('name', 'cd')->all();
        $this->kingakuRank = DB::table('tb_kingaku_rank_master')->where('disp_flg', '1')->orderBy('cd')->pluck('name', 'cd')->all();

        return $this;
    }

    public function senderName(): string
    {
        return (string) ($this->sender['kaishamei'] ?? '');
    }

    public function adminMaster(): array
    {
        return $this->adminMaster;
    }

    /** setDefaultFooter(): flags de admin_master.mail_footer [住所, 電話番号, メールアドレス, URL] ('' = mostrar) */
    public function defaultFooter(bool $deco): string
    {
        $nl = $deco ? '<br/>' : "\n";
        $s = $this->sender;
        $flags = array_pad(explode('<>', (string) ($this->adminMaster['mail_footer'] ?? '')), 4, '');
        $email = (string) ($s['email'] ?? '');
        $tel = (string) ($s['tel'] ?? '');

        $footer = '本メールは配信専用です。本メールアドレスに直接ご返信は出来ません。'.$nl;
        if ($email !== '' && $flags[2] === '') {
            $footer .= 'お問い合わせにつきましては、下記のメールアドレスにお問い合わせ下さい';
        }
        $footer .= $nl.$nl.'━━━━━━━━━━━'.$nl;
        $footer .= (string) ($s['kaishamei'] ?? '');

        if (($s['add'] ?? '') !== '' && $flags[0] === '') {
            $footer .= $nl.($s['add'] ?? '').($s['add_number'] ?? '').($s['add_build'] ?? '');
        }
        if ($tel !== '' && $flags[1] === '') {
            $footer .= $nl.'TEL：'.($deco ? '<a href="tel:'.$tel.'">'.$tel.'</a>' : $tel);
        }
        if ($email !== '' && $flags[2] === '') {
            $footer .= $nl.'E-Mail：'.($deco ? '<a href="mailto:'.$email.'">'.$email.'</a>' : $email);
        }
        if ($flags[3] === '') {
            $url = (string) ($s['shop_url'] ?? '') !== '' ? (string) $s['shop_url'] : $this->domain->hpAddress();
            $footer .= $nl.'URL：'.($deco ? '<a href="'.$url.'">'.$url.'</a>' : $url);
        }

        return $footer.$nl.'━━━━━━━━━━━'.$nl.$nl;
    }

    /**
     * addSimpleLogin(): enlace a Mi página con login_id/login_pass en md5.
     * $type: 'deco' (HTML), 'anketo' (texto de encuesta) o '' (texto).
     */
    public function simpleLogin(array $customer, string $type = '', ?int $mailmagCd = null): string
    {
        if ((string) ($customer['login_id'] ?? '') === '') {
            return '';
        }

        $extra = $mailmagCd !== null
            ? '&mmcd='.md5((string) $mailmagCd)
            : '&log_chk='.md5(($customer['id'] ?? '').($customer['login_pass'] ?? ''));
        $link = $this->domain->hpAddress().'user/login.php?login_id='.md5((string) $customer['login_id'])
            .'&login_pass='.md5((string) ($customer['login_pass'] ?? '')).$extra;

        return match ($type) {
            'deco' => '<br/><br/>※便利なマイページログインは<a href="'.$link.'">こちら。</a><br/>'.$link.'<br/>',
            'anketo' => "\n\n※アンケートフォームはこちら。\n".$link."\n",
            default => "\n\n※便利なマイページログインはこちら。\n".$link."\n",
        };
    }

    /**
     * replaceMessage(): sustituye las etiquetas {名前}, {ID}, {誕生月日}… en asunto y cuerpo.
     *
     * @return array{0: string, 1: string} [asunto, cuerpo]
     */
    public function replace(string $subject, string $body, array $customer, array $mailmag = []): array
    {
        $pairs = [
            '{名前}' => (string) ($customer['name'] ?? ''),
            '{ID}' => (string) ($customer['id'] ?? ''),
            '{誕生月日}' => $this->birthday((string) ($customer['kojin_birth'] ?? '')),
            '{住所}' => $this->address($customer),
            '{電話番号}' => (string) ($customer['tel'] ?? ''),
            '{スタンプ数}' => (string) (int) ($customer['stamp_count'] ?? 0),
            '{管理番号}' => (string) ($customer['kanri_id'] ?? ''),
            '{最終来店日}' => $this->lastVisit((string) ($customer['last_raiten_date'] ?? '')),
            '{使用金額ランク}' => (string) ($this->kingakuRank[$customer['kingaku_rank_now'] ?? ''] ?? ''),
            '{来店回数ランク}' => (string) ($this->raitenRank[$customer['raiten_rank_now'] ?? ''] ?? ''),
        ];

        // {日後:N}: N días después del 配信日 (solo en el cuerpo, como el legacy)
        if ($mailmag !== [] && preg_match_all('/\{日後:(\d+)\}/', $body, $m)) {
            foreach ($m[0] as $i => $tag) {
                $base = strtotime(($mailmag['配信日'] ?? '').' +'.$m[1][$i].' day');
                $body = str_replace($tag, $base === false ? '' : date('Y/m/d', $base), $body);
            }
        }

        // {予約時間} y {予約日}: solo en el cuerpo y solo para recordatorios de reserva
        if ((string) ($customer['start_time'] ?? '') !== '') {
            $body = str_replace('{予約時間}', $this->reservationTime((int) $customer['start_time']), $body);
        }
        if ((string) ($customer['moushikomi_date'] ?? '') !== '') {
            $body = str_replace('{予約日}', (string) $customer['moushikomi_date'], $body);
        }

        // {ポイント数}: saldo de tb_customer_point
        if (($customer['cd'] ?? '') !== '' && (str_contains($body, '{ポイント数}') || str_contains($subject, '{ポイント数}'))) {
            $pairs['{ポイント数}'] = (string) (int) DB::table('tb_customer_point')->where('tb_customer_cd', $customer['cd'])->value('service_point');
        }

        // {単語}: campos personalizados del cliente
        if (($customer['cd'] ?? '') !== '' && $this->wordList !== []) {
            $values = $this->wordValues((int) $customer['cd']);
            foreach ($this->wordList as $formCd => $name) {
                $pairs['{'.$name.'}'] = (string) ($values[$formCd] ?? '');
            }
        }

        $subject = strtr($subject, $pairs);
        $body = strtr($body, $pairs);

        // {アンケートURL[n]}: solo en el cuerpo
        if (preg_match_all('/\{アンケートURL\[([0-9]+?)\]\}/', $body, $m)) {
            foreach ($m[1] as $anketoCd) {
                $url = 'http://'.$this->domain->domain().'/hp/user/login.php?login_id='.md5((string) ($customer['login_id'] ?? ''))
                    .'&login_pass='.md5((string) ($customer['login_pass'] ?? '')).'&anketo_cd='.$anketoCd;
                $body = str_replace('{アンケートURL['.$anketoCd.']}', $url, $body);
            }
        }

        // {苗字}: primera palabra del nombre (el legacy solo lo hace si aparece en el cuerpo)
        if (str_contains($body, '{苗字}')) {
            $last = explode(' ', (string) ($customer['name'] ?? ''))[0];
            $body = str_replace('{苗字}', $last, $body);
            $subject = str_replace('{苗字}', $last, $subject);
        }

        return [$subject, $body];
    }

    /**
     * adjustDecomail(): HTML de TinyMCE → formato デコメール (div align, font color) y las imágenes
     * a cid:<archivo>. Devuelve el HTML y las imágenes a incrustar [nombre => ruta en disco].
     *
     * @return array{0: string, 1: array<string, string>}
     */
    public function adjustDeco(string $html): array
    {
        foreach (['center', 'left', 'right'] as $align) {
            $html = preg_replace('#<p style="text-align: '.$align.';">(.*)</p>#', '<div align="'.$align.'">$1</div>', $html);
        }
        $html = preg_replace_callback('#style="(.*?)color: rgb\(?([0-9]+, [0-9]+, [0-9]+)\)#', function ($m) {
            $hex = implode('', array_map(fn ($v) => sprintf('%02X', (int) trim($v)), explode(',', $m[2])));

            return 'style="color: #'.$hex;
        }, $html);
        $html = preg_replace('#<span(.[^>]*?)style="color: \#?([a-fA-F0-9]+);">(.[^</span>]*)</span>#', '<font color="#$2">$3</font>', $html);
        $html = strip_tags($html, '<a><p><img><font><div>');
        $html = preg_replace('#<span(.[^>]*?)style="(.*?)color: \#?([a-fA-F0-9]+);(.*?)">(.*)</span>#', '<font color="#$3">$5</font>', $html);
        $html = str_replace(['<p>', '</p>', '../decomail/'], ['<div>', '</div>', ''], $html);
        $html = preg_replace('#https?://(.[^"\']*)/js/tiny_mce#', 'js/tiny_mce', $html);

        $images = [];
        $pattern = '/<img([^>]*)src="?([\-_\.\!\~\*\'\(\)a-z0-9;\/\?:@&=\+\$,%#]+(jpg|jpeg|gif|png|bmp))/i';
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = basename($m[2]);
                $html = str_replace($m[2], 'cid:'.$name, $html);
                $images[$name] ??= $this->decoImagePath($m[2]);
            }
        }

        return [$html, $images];
    }

    /** Imagen de la デコメール: estáticos de tiny_mce en public/legacy o subidas en el disco public */
    private function decoImagePath(string $src): string
    {
        $static = public_path('legacy/system/category3/decomail/'.ltrim($src, '/'));

        return is_file($static) ? $static : storage_path('app/public/system/category3/decomail/'.ltrim($src, '/'));
    }

    /** `1990/04/05` → `4月5日` */
    private function birthday(string $birth): string
    {
        if ($birth === '') {
            return '';
        }
        $parts = explode('/', $birth);
        $month = ($parts[1] ?? '') !== '' ? (int) $parts[1].'月' : '';
        $day = ($parts[2] ?? '') !== '' ? (int) $parts[2].'日' : '';

        return $month.$day;
    }

    private function address(array $c): string
    {
        $address = ($c['pref'] ?? '').($c['add'] ?? '').($c['add_number'] ?? '').($c['add_build'] ?? '');

        return ($address !== '' && ($c['zip'] ?? '') !== '') ? '〒'.$c['zip'].' '.$address : $address;
    }

    private function lastVisit(string $date): string
    {
        $time = $date === '' ? false : strtotime($date);

        return $time === false ? '' : date('m月d日', $time);
    }

    /** Segundos desde medianoche → `H:i`; más de un día se muestra como 24時, 25時… */
    private function reservationTime(int $seconds): string
    {
        $hour = intdiv($seconds, 3600) % 24;
        $minute = intdiv($seconds % 3600, 60);
        if ($seconds > 86400) {
            $hour += 24;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    /** getWordList() de mailWordsReplace.php */
    private function loadWordList(): array
    {
        return DB::table('tb_search_form as tb1')
            ->join('tb_search_cate as tb2', 'tb1.tb_search_cate_cd', '=', 'tb2.cd')
            ->where('tb1.mail_word_flg', '1')
            ->whereRaw("COALESCE(tb2.record_type, '') = ''")
            ->where('tb1.disp_flg', '1')
            ->whereIn('tb1.hyouji_flg', ['1', '2'])
            ->where('tb2.disp_flg', '1')
            ->where('tb2.hyouji_flg', '1')
            ->whereNotIn('tb1.form_type', ['テーブル', 'メニュー項目'])
            ->orderBy('tb1.rank')
            ->pluck('tb1.name', 'tb1.cd')
            ->all();
    }

    /** Valor de cada campo personalizado del cliente con el formato del legacy */
    private function wordValues(int $customerCd): array
    {
        return DB::table('tb_customer_sub as tb1')
            ->join('tb_search_form as tb2', 'tb1.tb_search_form_cd', '=', 'tb2.cd')
            ->where('tb1.disp_flg', '1')
            ->where('tb1.tb_customer_cd', $customerCd)
            ->whereIn('tb2.cd', array_keys($this->wordList))
            ->selectRaw("tb2.cd, CASE
                WHEN tb2.form_type='数値テキスト' THEN tb1.float_data::text
                WHEN tb2.form_type='チェックボックス' THEN REPLACE(tb1.text_data, '<>', '　')
                WHEN tb2.form_type='月日' THEN SPLIT_PART(tb1.text_data, '/', 1)||'月'||SPLIT_PART(tb1.text_data, '/', 2)||'日'
                WHEN tb2.form_type='年' THEN tb1.text_data||'年'
                WHEN tb2.form_type='年月' THEN SPLIT_PART(tb1.text_data, '/', 1)||'年'||SPLIT_PART(tb1.text_data, '/', 2)||'月'
                WHEN tb2.form_type='年月日' OR tb2.form_type='起算日' THEN SPLIT_PART(tb1.text_data, '/', 1)||'年'||SPLIT_PART(tb1.text_data, '/', 2)||'月'||SPLIT_PART(tb1.text_data, '/', 3)||'日'
                ELSE tb1.text_data END AS data")
            ->pluck('data', 'cd')
            ->all();
    }
}

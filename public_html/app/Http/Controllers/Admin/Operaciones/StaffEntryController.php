<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\BelongPost;
use App\Models\Operaciones\StaffGroup;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Nucleo\Hatuban;
use App\Services\Operaciones\PosGateway;
use App\Services\Operaciones\StaffPhotoStore;
use App\Support\LegacyCheck;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * スタッフ新規登録 / スタッフ登録情報 修正: staff_entry.php → staff_entry_confirm.php →
 * staff_entry_register.php → staff_entry_complete.php, con la foto de inc/upload.php.
 *
 * Los datos viajan en crudo entre formulario y confirmación (los hidden se escapan al pintarlos);
 * al guardar se aplica get_form2 (LegacyText::input) como el legacy. Lo leído de tb_staff (ya escapado)
 * se des-escapa para volver al formulario.
 */
class StaffEntryController extends Controller
{
    /** Campos de texto del formulario (tb_staff), en el orden del legacy */
    public const TEXT_FIELDS = [
        'name_kana', 'name', 'sex', 'bloodtype', 'birthday', 'age', 'zip', 'pref', 'add_kana', 'add', 'add_number',
        'add_build_kana', 'add_build', 'email1', 'email2', 'mmail1', 'mmail2', 'tel', 'fax', 'mtel1', 'mtel2',
        'entranceday', 'retirementday', 'belongpost', 'employmentsystem', 'officialposition', 'salary', 'paidholiday',
        'kingaku_buai', 'reserve_flg', 'common_flg', 'rank', 'remarks', 'near_station1', 'near_station2', 'near_station3',
        'near_station4', 'traf_transport', 'traf_section1_1', 'traf_section1_2', 'traf_charge', 'traf_regularfee',
        'traf_remarks', 'hyouji_flg', 'photo_hyouji_flg', 'comment', 'login_pass',
    ];

    /** Campos que el formulario envía además de los de tb_staff */
    private const FORM_FIELDS = ['id', 'shop_cd', 'belongpost1', 'tb_staff_group_cd', 'pic_name'];

    public function __construct(private readonly StaffPhotoStore $photos)
    {
    }

    /** スタッフ新規登録 (staff_entry.php sin cd): スタッフID provisional con createID */
    public function create(Request $request): View
    {
        $shopCd = (int) $request->session()->get('shop_cd', auth('admin')->user()->shop_cd);

        return $this->form(['id' => $this->createId($shopCd), 'shop_cd' => (string) $shopCd], 'new');
    }

    /** スタッフ登録情報 修正 (staff_entry.php?cd=) */
    public function edit(int $cd): View
    {
        $staff = Staff::where('cd', $cd)->firstOrFail();

        return $this->form($this->fromStaff($staff) + ['pic_name' => ''], 'up', $staff);
    }

    /** 書き直す: vuelve al formulario con lo enviado */
    public function rewrite(Request $request): View
    {
        [$data, $act, $staff] = $this->input($request);

        return $this->form($data, $act, $staff);
    }

    /** この内容で確認する (staff_entry_confirm.php): con errores, el formulario marcado; si no, la confirmación */
    public function confirm(Request $request): View
    {
        [$data, $act, $staff] = $this->input($request);
        $check = $this->check($data, $act, $staff);

        if ($check->fails()) {
            return $this->form($data, $act, $staff, $check);
        }

        return view('admin.operaciones.staff.confirm', $this->shared($data, $act, $staff) + [
            'imageUrl' => $this->imageUrl($data['pic_name'], $staff, confirm: true),
        ]);
    }

    /** この内容で登録する (staff_entry_register.php) */
    public function store(Request $request, Hatuban $hatuban, PosGateway $pos): RedirectResponse
    {
        [$data, $act, $staff] = $this->input($request);
        if ($this->check($data, $act, $staff)->fails()) {
            // Llegó sin pasar por la confirmación: se vuelve a ella para mostrar los errores
            return to_route($act === 'new' ? 'admin.operaciones.staff.create' : 'admin.operaciones.staff.edit', $staff?->cd);
        }

        $cd = DB::transaction(fn () => $act === 'new' ? $this->insert($data, $hatuban) : $this->update($staff, $data));

        if (StaffPhotoStore::isToken($data['pic_name']) && $data['pic_name'] !== '1') {
            $this->photos->moveToStaff($data['pic_name'], $cd);
        }
        $pos->syncStaff($cd);

        return to_route('admin.operaciones.staff.complete', $cd);
    }

    /** スタッフ登録情報 (staff_entry_complete.php): lo guardado + 続けて新規登録する / スタッフ一覧から検索する */
    public function complete(int $cd): View
    {
        $staff = Staff::withDeleted()->where('cd', $cd)->firstOrFail();
        $data = $this->fromStaff($staff);

        return view('admin.operaciones.staff.complete', $this->shared($data, 'up', $staff) + [
            'imageUrl' => filled($staff->pic_name) ? '/hp/uploads/c5/staff/'.$cd.'/'.$cd.'.jpg?pcc='.time() : null,
            'groupNames' => StaffGroup::withDeleted()->orderBy('rank')->pluck('name', 'cd'),
        ]);
    }

    /** Subida AJAX de la foto (inc/upload.php): responde el token o "error" */
    public function uploadPhoto(Request $request): Response
    {
        $file = $request->file('userfile');
        $token = $file && $file->isValid() && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif'], true)
            ? $this->photos->storeTemp($file)
            : null;

        return response($token ?? 'error', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** Foto temporal (uploads/temp/<token>.jpg del legacy) */
    public function tempPhoto(string $token): StreamedResponse
    {
        abort_unless($this->photos->tempExists($token), 404);

        return Storage::disk('local')->response($this->photos->tempPath($token), null, ['Content-Type' => 'image/jpeg']);
    }

    // ── Datos ───────────────────────────────────────────────────────────────

    /** @return array{0: array<string, string>, 1: string, 2: ?Staff} datos crudos, act ('new'|'up') y el empleado en edición */
    private function input(Request $request): array
    {
        $act = $request->input('act') === 'up' ? 'up' : 'new';
        $staff = $act === 'up' ? Staff::where('cd', (int) $request->input('cd'))->firstOrFail() : null;
        Gate::authorize($act === 'up' ? 'kengen.1024.3' : 'kengen.1024.2');

        $data = [];
        foreach ([...self::TEXT_FIELDS, ...self::FORM_FIELDS] as $field) {
            $value = $request->input($field);
            $data[$field] = is_scalar($value) ? (string) $value : '';
        }
        if (! auth('admin')->user()->isMaster()) {
            $data['shop_cd'] = (string) $request->session()->get('shop_cd', auth('admin')->user()->shop_cd);
        }
        if ($staff) {
            $data['id'] = (string) $staff->id;
        }

        return [$data, $act, $staff];
    }

    /** Fila de tb_staff (escapada con get_form2) como datos crudos del formulario */
    private function fromStaff(Staff $staff): array
    {
        $data = ['id' => (string) $staff->id, 'shop_cd' => (string) $staff->shop_cd, 'belongpost1' => '',
            'tb_staff_group_cd' => (string) $staff->tb_staff_group_cd];
        foreach (self::TEXT_FIELDS as $field) {
            $data[$field] = htmlspecialchars_decode((string) $staff->getRawOriginal($field), ENT_COMPAT | ENT_HTML401);
        }

        return $data;
    }

    /** Validaciones de staff_entry_confirm.php (error_check.php) y ID duplicado */
    private function check(array &$data, string $act, ?Staff $staff): LegacyCheck
    {
        foreach (['tel', 'fax', 'mtel1', 'mtel2'] as $field) {
            $data[$field] = (string) LegacyCheck::replaceTel($data[$field]);
        }

        $check = new LegacyCheck;
        if ($act !== 'up') {
            $check->alnumRequired('id', $data['id']);
        }
        $check->blank('shop_cd', $data['shop_cd'])
            ->katakana('name_kana', $data['name_kana'])
            ->blank('name', $data['name'])
            ->date('birthday', $data['birthday'])
            ->num('age', $data['age'])
            ->zip('zip', $data['zip'])
            ->katakana('add_kana', $data['add_kana'])
            ->katakanaAlnum('add_build_kana', $data['add_build_kana']);
        foreach (['email1', 'email2', 'mmail1', 'mmail2'] as $field) {
            $check->mail($field, $data[$field]);
        }
        foreach (['tel', 'fax', 'mtel1', 'mtel2'] as $field) {
            $check->num($field, $data[$field]);
        }
        $check->date('entranceday', $data['entranceday'])->date('retirementday', $data['retirementday']);
        foreach (['salary', 'paidholiday', 'near_station4', 'traf_charge', 'traf_regularfee'] as $field) {
            $check->num($field, $data[$field]);
        }
        if (! $this->isAdminAccount($staff)) {
            $check->alnumRequired('login_pass', $data['login_pass']);
        }
        $check->num('rank', $data['rank']);

        $duplicate = Staff::where('id', LegacyText::input($data['id']))
            ->when($staff, fn ($q) => $q->where('cd', '!=', $staff->cd))
            ->exists();
        if ($duplicate) {
            $check->add('id', '<p class="m0">※既に使用されているIDです。</p>');
        }

        return $check;
    }

    /** Alta: cd con createHatsuban, スタッフID y ログインID con createID, contador hatuban 'tb_staff' */
    private function insert(array $data, Hatuban $hatuban): int
    {
        $hatuban->next('tb_staff');
        DB::statement('LOCK TABLE tb_staff IN SHARE ROW EXCLUSIVE MODE');

        $cd = (int) (time().'000'.sprintf('%06d', random_int(0, 999999))); // createHatsuban('')
        $id = $this->createId((int) $data['shop_cd']);
        $now = (string) time();

        DB::table('tb_staff')->insert($this->columns($data) + [
            'cd' => $cd,
            'office_cd' => 1,
            'id' => $id,
            'login_id' => $id,
            'login_pass' => LegacyText::input($data['login_pass']),
            'rank_pass' => '',
            'pos_rank' => 10,
            'age' => $data['age'] ? (int) $data['age'] : null,
            'touroku_date' => $now,
            'update_date' => $now,
            'disp_flg' => Staff::ACTIVE_FLAG,
        ]);

        return $cd;
    }

    /** Edición: el master (o el cd 1) conserva su contraseña y su grupo */
    private function update(Staff $staff, array $data): int
    {
        $columns = $this->columns($data, $staff) + ['update_date' => (string) time(), 'rank_pass' => ''];
        if ($data['age']) {
            $columns['age'] = (int) $data['age'];
        }
        if ($this->isAdminAccount($staff)) {
            unset($columns['tb_staff_group_cd'], $columns['rank_pass']);
        } else {
            $columns['login_pass'] = LegacyText::input($data['login_pass']);
        }

        DB::table('tb_staff')->where('cd', $staff->cd)->update($columns);

        return $staff->cd;
    }

    /** Columnas comunes de alta y edición (staff_entry_register.php) */
    private function columns(array $data, ?Staff $staff = null): array
    {
        // 所属部署: el texto libre gana al desplegable y se agrega a tb_belongpost si no existe
        if (LegacyText::trim($data['belongpost1']) !== '') {
            $data['belongpost'] = $data['belongpost1'];
            BelongPost::findOrCreateByName(LegacyText::input($data['belongpost1']));
        }

        $columns = ['shop_cd' => (int) $data['shop_cd'], 'pic_name' => $this->picName($data['pic_name'], $staff)];
        $text = array_diff(self::TEXT_FIELDS, ['age', 'rank', 'kingaku_buai', 'login_pass', 'hyouji_flg', 'photo_hyouji_flg', 'comment']);
        foreach ($text as $field) {
            $columns[$field] = LegacyText::input($data[$field]);
        }
        if ($this->hasHomepage()) {
            foreach (['hyouji_flg', 'photo_hyouji_flg', 'comment'] as $field) {
                $columns[$field] = LegacyText::input($data[$field]);
            }
        }

        return $columns + [
            'rank' => $data['rank'] !== '' ? (int) $data['rank'] : null,
            'kingaku_buai' => (float) $data['kingaku_buai'],
            'tb_staff_group_cd' => $data['tb_staff_group_cd'] !== '' ? (int) $data['tb_staff_group_cd'] : null,
            'synchronize_flg' => '',
        ];
    }

    /** pic_name: 'delete' → sin foto; token o '1' → con foto; vacío → lo que tenía */
    private function picName(string $posted, ?Staff $staff): string
    {
        return match (true) {
            $posted === 'delete' => '',
            $posted !== '' => '1',
            default => filled($staff?->pic_name) ? '1' : '',
        };
    }

    /** createID(): <tienda><1><nº de filas de tb_staff + 1 en 5 cifras> */
    private function createId(int $shopCd): string
    {
        $count = DB::table('tb_staff')->count() + 1;

        return $shopCd.'1'.($count > 99999 ? $count : sprintf('%05d', $count));
    }

    private function isAdminAccount(?Staff $staff): bool
    {
        return $staff !== null && ($staff->login_id === Staff::MASTER_LOGIN_ID || $staff->cd === 1);
    }

    private function hasHomepage(): bool
    {
        return DB::table('tb_base_system_grandchild')->where('tb_base_system_child_cd', 50)->where('system_flg', '1')->exists();
    }

    // ── Vistas ──────────────────────────────────────────────────────────────

    private function form(array $data, string $act, ?Staff $staff = null, ?LegacyCheck $check = null): View
    {
        $data += array_fill_keys([...self::TEXT_FIELDS, ...self::FORM_FIELDS], '');

        return view('admin.operaciones.staff.form', $this->shared($data, $act, $staff) + [
            'check' => $check ?? new LegacyCheck,
            'imageUrl' => $this->imageUrl($data['pic_name'], $staff),
            'belongposts' => DB::table('tb_belongpost')->orderBy('cd')->pluck('belongpost'),
            'employmentSystems' => DB::table('tb_employmentsystem')->orderBy('cd')->pluck('employmentsystem'),
            'officialPositions' => DB::table('tb_officialposition')->orderBy('cd')->pluck('officialposition'),
        ]);
    }

    private function shared(array $data, string $act, ?Staff $staff): array
    {
        $shops = Shop::query()
            ->when(! auth('admin')->user()->isMaster(), fn ($q) => $q->where('cd', auth('admin')->user()->shop_cd))
            ->orderBy('cd')->pluck('kaishamei', 'cd');

        return [
            'data' => $data,
            'act' => $act,
            'staff' => $staff,
            'isAdminAccount' => $this->isAdminAccount($staff),
            'shops' => $shops,
            'shopNames' => Shop::withDeleted()->pluck('kaishamei', 'cd'),
            'groups' => StaffGroup::ordered()->pluck('name', 'cd'),
            'hasHomepage' => $this->hasHomepage(),
        ];
    }

    /** Imagen que se muestra: temporal subida, la guardada del empleado o ninguna */
    private function imageUrl(string $picName, ?Staff $staff, bool $confirm = false): ?string
    {
        return match (true) {
            $picName === 'delete' => null,
            StaffPhotoStore::isToken($picName) && $picName !== '1' => route('admin.operaciones.staff.photo.temp', $picName),
            $staff !== null && (filled($staff->pic_name) || ($confirm && $picName === '1')) => '/hp/uploads/c5/staff/'.$staff->cd.'/'.$staff->cd.'.jpg?pcc='.time(),
            default => null,
        };
    }
}

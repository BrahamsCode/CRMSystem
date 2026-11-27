<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Customers\CustomerFormService;
use App\Services\Promotions\AnketoAnswerService;
use App\Services\Promotions\AnketoFamily;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * アンケートテスト (anketo_test.php + anketo_test_register.php): herramienta interna sin menú que pinta una encuesta
 * al azar para un socio al azar y guarda la respuesta como lo haría el cliente (grupo category_inner_group nuevo).
 * Los campos se pintan, comprueban y guardan con las piezas de S2 (partials/custom_field, createform_errorcheck,
 * createform_sql).
 */
class AnketoTestController extends Controller
{
    public function __construct(private readonly AnketoAnswerService $answers) {}

    public function show(Request $request, CustomerFormService $fields, string $family): View|Response
    {
        $family = AnketoFamily::from($family);
        $posted = $request->isMethod('post') && $request->input('act') === 'up';

        if ($posted) {
            $cateCd = (int) $request->input('anketo_cate_cd');
            $customerCd = (int) $request->input('kaiin_cd');
        } else {
            $cateCd = (int) DB::table($family->formTable())->distinct()->pluck('tb_c3_anketo_cate_cd')->shuffle()->first();
            // El legacy inventaba un número entre 1000 y 10134; aquí, un socio que exista (si no hay, el mismo rango)
            $customerCd = (int) (DB::table('tb_customer')->inRandomOrder()->value('cd') ?? mt_rand(1000, 10134));
        }

        $cate = $family->cates()->withDeleted()->find($cateCd);
        if (! $cate) {
            return response('アンケートはありません！！！');
        }
        $forms = $family->forms()->where('tb_c3_anketo_cate_cd', $cate->cd)->where('disp_flg', '1')->orderBy('rank')->get();
        if ($forms->isEmpty()) {
            return response('フォームパーツはありません！！！');
        }

        $errors = [];
        $data = $posted ? $request->except('_token') : [];
        if ($posted) {
            foreach ($forms as $form) {
                $error = $fields->customFieldError($form->getAttributes(), $data);
                if ($error !== '') {
                    $errors[$form->cd] = $error;
                }
            }
            if ($errors === []) {
                $verify = (string) $request->input('add_record_verify');
                // Doble clic / recarga: el legacy solo registra si el token cambió
                if ($verify === '' || session('promocion.anketo_test_verify') !== $verify) {
                    session(['promocion.anketo_test_verify' => $verify]);
                    $this->answers->storeTest($family, $cate, $forms, $customerCd, $data);
                }

                return view('admin.promocion.anketo.anketo_test_register', [
                    'family' => $family,
                    'next' => $family->route('test', ['ok' => 'ok']),
                ]);
            }
        }

        return view('admin.promocion.anketo.anketo_test', [
            'family' => $family,
            'cate' => $cate,
            'forms' => $forms,
            'values' => $data,
            'errors' => $errors,
            'customerCd' => $customerCd,
            'ok' => $request->input('ok') === 'ok',
        ]);
    }
}

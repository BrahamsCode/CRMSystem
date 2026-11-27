<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\MailTemplateCategoryRequest;
use App\Models\Promotions\MailTemplateCategory;
use App\Services\Promotions\RankOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * テンプレートカテゴリ管理 (popup): mail_template_category.php, ajax_mail_template_category.php,
 * mail_template_category_register.php
 */
class TemplateCategoryController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.promocion.mail_mag.mail_template_category', [
            'category' => $request->filled('cd') ? MailTemplateCategory::withDeleted()->find($request->integer('cd')) : null,
        ]);
    }

    public function ajax(Request $request, RankOrder $order): View
    {
        $cd = $request->integer('send_targetCd');

        match ((string) $request->input('send_type')) {
            RankOrder::UP, RankOrder::DOWN => $cd && $request->filled('send_vl') ? $order->move(MailTemplateCategory::query(), $cd, $request->input('send_type')) : null,
            'hyouji' => $cd ? MailTemplateCategory::whereKey($cd)->update(['hyouji_flg' => (string) $request->input('send_vl') === '1' ? '9' : '1']) : null,
            'del' => $cd ? MailTemplateCategory::whereKey($cd)->update(['disp_flg' => '9', 'rank' => null]) : null,
            default => null,
        };

        return view('admin.promocion.mail_mag.ajax_mail_template_category', [
            'categories' => MailTemplateCategory::query()->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get(),
        ]);
    }

    public function register(MailTemplateCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $category = $request->filled('cd')
                ? MailTemplateCategory::withDeleted()->findOrFail($request->integer('cd'))
                : new MailTemplateCategory;

            $category->fill(['name' => (string) $request->input('name'), 'disp_flg' => '1', 'hyouji_flg' => '1']);
            $category->save();

            if ($category->rank === null) {
                $category->rank = $category->cd;
                $category->save();
            }
        });

        return redirect()->route('admin.promocion.template-categories.index');
    }
}

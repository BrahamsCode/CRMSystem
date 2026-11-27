<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\MailTemplateRequest;
use App\Models\Promotions\MailTemplate;
use App\Models\Promotions\MailTemplateCategory;
use App\Services\Promotions\MailReplaceTags;
use App\Services\Promotions\RankOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * テンプレート管理 (system/category3/mail_mag/mail_template*.php, ajax_mail_template.php)
 */
class TemplateController extends Controller
{
    /** mail_template.php */
    public function index(): View
    {
        return view('admin.promocion.mail_mag.mail_template', [
            'categories' => $this->visibleCategories(),
        ]);
    }

    /** mail_template_entry.php (popup) */
    public function entry(Request $request, MailReplaceTags $tags): View
    {
        return view('admin.promocion.mail_mag.mail_template_entry', [
            'template' => $request->filled('cd') ? MailTemplate::withDeleted()->find($request->integer('cd')) : null,
            'categories' => $this->visibleCategories(),
            'replaceTags' => $tags->all(),
        ]);
    }

    /** ajax_mail_template.php: ▲▼, 表示切替 y 削除, y devuelve la lista */
    public function ajax(Request $request, RankOrder $order): View
    {
        $cd = $request->integer('send_targetCd');

        match ((string) $request->input('send_type')) {
            RankOrder::UP, RankOrder::DOWN => $cd && $request->filled('send_vl') ? $order->move(MailTemplate::query(), $cd, $request->input('send_type')) : null,
            'hyouji' => $cd ? MailTemplate::whereKey($cd)->update(['hyouji_flg' => (string) $request->input('send_vl') === '1' ? '9' : '1']) : null,
            'del' => $cd ? MailTemplate::whereKey($cd)->update(['disp_flg' => '9', 'rank' => null]) : null,
            default => null,
        };

        $sendCate = (string) $request->input('send_cate', '');

        return view('admin.promocion.mail_mag.ajax_mail_template', [
            'templates' => MailTemplate::query()
                ->with('category')
                ->where('disp_flg', '1')
                ->when($sendCate !== '', fn ($q) => $q->where('tb_mail_template_category_cd', (int) $sendCate))
                ->orderBy('rank')->orderBy('cd')
                ->get(),
            'sendCate' => $sendCate,
        ]);
    }

    /** mail_template_register.php: alta (cd y rank de hatuban) o modificación, y vuelve al popup con comp=1 */
    public function register(MailTemplateRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $template = $request->filled('cd')
                ? MailTemplate::withDeleted()->findOrFail($request->integer('cd'))
                : new MailTemplate;

            $template->fill([
                'tb_mail_template_category_cd' => $request->integer('tb_mail_template_category_cd'),
                'title' => (string) $request->input('title'),
                'mail_type' => (string) $request->input('mail_type', ''),
                'naiyou' => (string) $request->input($request->input('mail_type') === MailTemplate::MAIL_TYPE_DECO ? 'naiyou_deco' : 'naiyou', ''),
                'disp_flg' => '1',
                'hyouji_flg' => '1',
            ]);
            $template->save();

            // Alta: el legacy pone rank = cd
            if ($template->rank === null) {
                $template->rank = $template->cd;
                $template->save();
            }
        });

        return redirect()->route('admin.promocion.templates.entry', ['comp' => 1]);
    }

    private function visibleCategories()
    {
        return MailTemplateCategory::query()->where('hyouji_flg', '1')->orderBy('rank')->orderBy('cd')->get();
    }
}

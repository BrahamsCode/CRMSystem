<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\Appview\AppviewDomain;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * appview: páginas informativas. introduction.php y detail_view.php quedaron descartadas (leen la BD `admin`,
 * fuera de alcance).
 */
class PageController extends Controller
{
    use RendersAppview;

    /**
     * privacyPage.php?domain=: プライバシーポリシー (texto de contents_master mypage/privacy_policy_text o el fijo) con
     * el nombre de la primera tienda. Los dominios del POS (swa/rwa) no se conectan: «接続できませんでした».
     */
    public function privacy(Request $request): Response
    {
        $domain = $request->input('domain');
        if (empty($domain) || ! is_string($domain)) {
            return Out::text('不正なリクエストです');
        }

        $package = AppviewDomain::package($domain);
        if ($package === null) {
            return Out::text('解析エラーです。');
        }
        if ($package !== 'crm' || ! AppviewDomain::usesCrmDatabase($domain)) {
            return Out::text('接続できませんでした');
        }

        $text = (string) DB::table('contents_master')->where('category', 'mypage')->where('field', 'privacy_policy_text')
            ->orderByDesc('update_date')->limit(1)->value('text_data');

        $shopMasterName = '';
        foreach (DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('kaishamei') as $name) {
            if (empty($shopMasterName)) {
                $shopMasterName = (string) $name;
            }
        }

        return $this->page('privacy', $request, [
            'domain' => $domain,
            'privacy_policy_text' => $text,
            'shopMasterName' => $shopMasterName,
        ]);
    }
}

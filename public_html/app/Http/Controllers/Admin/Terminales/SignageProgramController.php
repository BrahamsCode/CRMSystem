<?php

namespace App\Http\Controllers\Admin\Terminales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Terminales\SignageProgramRequest;
use App\Models\Terminales\SignageProgram;
use App\Services\Terminales\SignageProgramService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** 放映プログラム (category7/digital_signage/movie_program_*.php) */
class SignageProgramController extends Controller
{
    public function __construct(private SignageProgramService $programs) {}

    /** movie_program_list.php (GET y POST: el selector de 件数 y «<<一覧へ戻る» envían por POST) */
    public function index(Request $request): View
    {
        return view('admin.terminales.signage-programs.index',
            $this->programs->listing($request, route('admin.terminales.signage-programs.index')));
    }

    /** movie_program_new.php (GET y POST: el buscador de 選択可能ファイル vuelve aquí por POST) */
    public function create(Request $request): View
    {
        return $this->form(null, $request);
    }

    public function store(SignageProgramRequest $request): View
    {
        $program = $this->programs->save(null, $request->validated());

        return $this->processing('new', $program);
    }

    /** movie_program_detail.php (GET y POST: «<<詳細へ戻る» envía por POST) */
    public function show(int $cd): View
    {
        $program = $this->programs->find($cd) ?? abort(404);

        return view('admin.terminales.signage-programs.show', $this->programs->showData($program));
    }

    /** movie_program_update.php (GET y POST: 編集する y el buscador envían por POST) */
    public function edit(Request $request, int $cd): View
    {
        return $this->form($this->programs->findOwned($cd) ?? abort(404), $request);
    }

    public function update(SignageProgramRequest $request, int $cd): View
    {
        $program = $this->programs->findOwned($cd) ?? abort(404);
        $this->programs->save($program, $request->validated());

        return $this->processing('update', $program);
    }

    /** movie_program_delete.php: «削除中です。» y vuelta al listado */
    public function destroy(Request $request, int $cd): View
    {
        $program = $this->programs->find($cd) ?? abort(404);
        if ($request->input('delete_flg') === 'del') {
            $this->programs->delete($program);
        }

        return view('admin.terminales.partials.processing', [
            'family' => 'programs',
            'action' => 'delete',
            'refreshUrl' => route('admin.terminales.signage-programs.index', [
                'shop_cd' => $request->input('shop_cd'),
                'movie_program_cd' => $request->input('movie_program_cd'),
                'page' => $request->input('page'),
                'kensu' => $request->input('kensu'),
            ]),
        ]);
    }

    private function form(?SignageProgram $program, Request $request): View
    {
        return view('admin.terminales.signage-programs.form',
            $this->programs->formData($program, $request, $request->old('tb_screensaver_cd')));
    }

    /** «登録中です。» con Refresh al detalle (movie_program_register.php) */
    private function processing(string $action, SignageProgram $program): View
    {
        return view('admin.terminales.partials.processing', [
            'family' => 'programs',
            'action' => $action,
            'refreshUrl' => route('admin.terminales.signage-programs.show', [$program->cd, 'register_type' => $action]),
        ]);
    }
}

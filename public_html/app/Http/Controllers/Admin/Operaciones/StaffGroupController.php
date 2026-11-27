<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operaciones\StaffGroupRequest;
use App\Models\Operaciones\StaffGroup;
use App\Services\Operaciones\StaffGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** スタッフグループ設定 (system/category5/staff/staff_group.php) */
class StaffGroupController extends Controller
{
    public function __construct(private readonly StaffGroupService $groups)
    {
    }

    /** Alta arriba (o edición con ?cd=) y lista abajo */
    public function index(Request $request): View
    {
        $group = $request->filled('cd') ? StaffGroup::find((int) $request->query('cd')) : null;

        return view('admin.operaciones.staff-groups.index', [
            'group' => $group,
            'groups' => StaffGroup::ordered()->get(),
        ]);
    }

    public function store(StaffGroupRequest $request): RedirectResponse
    {
        $this->groups->create($request->validated('name'));

        return to_route('admin.operaciones.staff-groups.index');
    }

    public function update(StaffGroupRequest $request, int $cd): RedirectResponse
    {
        $this->groups->rename(StaffGroup::findOrFail($cd), $request->validated('name'));

        return to_route('admin.operaciones.staff-groups.index');
    }

    public function destroy(int $cd): RedirectResponse
    {
        StaffGroup::find($cd)?->delete();

        return to_route('admin.operaciones.staff-groups.index');
    }

    /** ▲▼ (?thisCd=&kind=up|down) */
    public function sort(Request $request): RedirectResponse
    {
        $cd = (string) $request->query('thisCd', '');
        if (preg_match('/^[0-9]+$/', $cd)) {
            $this->groups->move((int) $cd, $request->query('kind') === 'up' ? 'up' : 'down');
        }

        return to_route('admin.operaciones.staff-groups.index');
    }
}

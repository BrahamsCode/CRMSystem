<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\MessageChannel;
use App\Enums\RuleTrigger;
use App\Models\Coupon;
use App\Models\MessageRule;
use App\Models\MessageTemplate;
use App\Services\Customers\CustomerSearch;
use App\Services\Customers\FilterOptions;
use App\Services\Promotions\MessageRenderer;
use App\Services\Promotions\RuleRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * Envíos automáticos: seguimiento (フォロー), programados (自動) y recordatorios
 * de reserva (リマインダー), para email y push.
 */
class RuleController extends ModuleController
{
    public function index(): View
    {
        $rules = MessageRule::with(['shop', 'coupon'])
            ->withCount('messages')
            ->orderBy('trigger')
            ->orderBy('name')
            ->get();

        return view('admin.promotions.rules.index', [
            'groups' => $rules->groupBy(fn (MessageRule $r) => $r->trigger->group()),
            'triggers' => RuleTrigger::cases(),
            'runner' => app(RuleRunner::class),
        ]);
    }

    public function create(Request $request): View
    {
        $trigger = RuleTrigger::tryFrom($request->integer('trigger')) ?? RuleTrigger::AfterVisit;

        return view('admin.promotions.rules.form', $this->formData(new MessageRule([
            'trigger' => $trigger,
            'channel' => MessageChannel::TextEmail,
            'days' => $trigger->usesDays() ? 3 : null,
            'timing' => 1,
            'run_hour' => 10,
            'run_minute' => 0,
            'months' => [],
            'month_days' => [],
            'weekdays' => [],
            'filters' => [],
            'status' => 1,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $rule = MessageRule::create($this->validated($request));

        return redirect()->route('admin.promotions.rules.index')
            ->with('status', "Automatización «{$rule->name}» creada. Próximo envío: " . ($rule->nextRunAt()?->format('d/m/Y H:i') ?? '—') . '.');
    }

    public function edit(MessageRule $rule): View
    {
        return view('admin.promotions.rules.form', $this->formData($rule));
    }

    public function update(Request $request, MessageRule $rule): RedirectResponse
    {
        $rule->update($this->validated($request));

        return redirect()->route('admin.promotions.rules.index')->with('status', "Automatización «{$rule->name}» guardada.");
    }

    public function toggle(MessageRule $rule): RedirectResponse
    {
        $rule->update(['status' => $rule->status === 1 ? 0 : 1]);

        return back()->with('status', $rule->status === 1 ? "«{$rule->name}» activada." : "«{$rule->name}» pausada.");
    }

    /** Ejecutar ahora para hoy, sin esperar a la hora configurada */
    public function run(MessageRule $rule, RuleRunner $runner): RedirectResponse
    {
        $message = $runner->run($rule);

        return $message
            ? redirect()->route('admin.promotions.messages.show', $message)->with('status', "Enviando a {$message->recipient_count} clientes.")
            : back()->with('status', 'Hoy no hay ningún cliente que cumpla esta automatización.');
    }

    public function destroy(MessageRule $rule): RedirectResponse
    {
        $rule->delete();

        return redirect()->route('admin.promotions.rules.index')->with('status', "Automatización «{$rule->name}» eliminada.");
    }

    private function formData(MessageRule $rule): array
    {
        return [
            'rule' => $rule,
            'triggers' => RuleTrigger::cases(),
            'channels' => MessageChannel::cases(),
            'templates' => MessageTemplate::active()->ordered()->get(['id', 'name', 'category', 'subject', 'body', 'html_flg']),
            'couponList' => Coupon::active()->orderByDesc('id')->get(['id', 'name']),
            'variables' => MessageRenderer::variables(),
            'hasReservations' => Schema::hasTable('reservations'),
            'todayCount' => $rule->exists ? app(RuleRunner::class)->previewCount($rule) : null,
        ] + FilterOptions::for($this->shop()?->id);
    }

    private function validated(Request $request): array
    {
        $trigger = RuleTrigger::tryFrom($request->integer('trigger'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['required', new Enum(RuleTrigger::class)],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'days' => [$trigger?->usesDays() ? 'required' : 'nullable', 'integer', 'min:0', 'max:365'],
            'timing' => ['nullable', 'in:1,2'],
            'months' => [$trigger === RuleTrigger::Dates ? 'required' : 'nullable', 'array'],
            'months.*' => ['integer', 'between:1,12'],
            'month_days' => [$trigger === RuleTrigger::Dates ? 'required' : 'nullable', 'array'],
            'month_days.*' => ['string', 'regex:/^([1-9]|[12][0-9]|3[01]|end)$/'],
            'weekdays' => [$trigger === RuleTrigger::Weekdays ? 'required' : 'nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'run_hour' => ['required', 'integer', 'between:0,23'],
            'run_minute' => ['required', 'integer', 'in:0,5,10,15,20,25,30,35,40,45,50,55'],
            'channel' => ['required', new Enum(MessageChannel::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'coupon_id' => ['nullable', Rule::exists('coupons', 'id')->whereNull('deleted_at')],
            'mobile_only_flg' => ['nullable', 'in:0,1'],
            'status' => ['nullable', 'in:0,1'],
            'filters' => ['nullable', 'array'],
        ], [
            'months.required' => 'Elige al menos un mes.',
            'month_days.required' => 'Elige al menos un día.',
            'weekdays.required' => 'Elige al menos un día de la semana.',
        ], ['name' => 'nombre', 'days' => 'días', 'body' => 'contenido']);

        $filters = (array) $request->input('filters', []);
        $valid = Validator::make($filters, CustomerSearch::rules())->valid();

        return [
            'filters' => CustomerSearch::clean(array_intersect_key($filters, $valid)),
            'months' => array_map('intval', $data['months'] ?? []),
            'month_days' => array_values($data['month_days'] ?? []),
            'weekdays' => array_map('intval', $data['weekdays'] ?? []),
            'timing' => (int) ($data['timing'] ?? 1),
            'mobile_only_flg' => (int) ($data['mobile_only_flg'] ?? 0),
            'status' => (int) ($data['status'] ?? 0),
            'days' => $trigger?->usesDays() ? (int) $data['days'] : null,
        ] + $data;
    }
}

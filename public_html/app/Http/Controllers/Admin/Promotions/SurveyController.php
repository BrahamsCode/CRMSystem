<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\CouponUsage;
use App\Enums\CustomFieldType;
use App\Models\Coupon;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Encuestas (アンケート) y sus resultados */
class SurveyController extends ModuleController
{
    /** Tipos de pregunta que tiene sentido usar en una encuesta */
    public const TYPES = [
        CustomFieldType::Text, CustomFieldType::Textarea, CustomFieldType::Numeric,
        CustomFieldType::Radio, CustomFieldType::Select, CustomFieldType::Checkbox, CustomFieldType::YearMonthDay,
    ];

    public function index(): View
    {
        return view('admin.promotions.surveys.index', [
            'surveys' => Survey::with(['shop', 'coupon'])->withCount(['questions', 'responses'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.surveys.form', $this->formData(new Survey(['status' => 1])));
    }

    public function store(Request $request): RedirectResponse
    {
        $survey = DB::transaction(function () use ($request) {
            [$data, $questions] = $this->validated($request);
            $survey = Survey::create($data);
            $this->syncQuestions($survey, $questions);

            return $survey;
        });

        return redirect()->route('admin.promotions.surveys.show', $survey)->with('status', "Encuesta «{$survey->name}» creada.");
    }

    public function show(Survey $survey): View
    {
        $survey->load(['questions', 'coupon', 'shop']);
        $responses = $survey->responses()->with('customer')->latest('answered_at');

        return view('admin.promotions.surveys.show', [
            'survey' => $survey,
            'results' => $this->results($survey),
            'responses' => $responses->paginate(10),
            'total' => $survey->responses()->count(),
            'link' => route('promotions.public.survey', $survey),
        ]);
    }

    public function edit(Survey $survey): View
    {
        return view('admin.promotions.surveys.form', $this->formData($survey->load('questions')));
    }

    public function update(Request $request, Survey $survey): RedirectResponse
    {
        DB::transaction(function () use ($request, $survey) {
            [$data, $questions] = $this->validated($request);
            $survey->update($data);
            $this->syncQuestions($survey, $questions);
        });

        return redirect()->route('admin.promotions.surveys.show', $survey)->with('status', 'Encuesta guardada.');
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $survey->delete();

        return redirect()->route('admin.promotions.surveys.index')->with('status', "Encuesta «{$survey->name}» eliminada.");
    }

    private function formData(Survey $survey): array
    {
        return [
            'survey' => $survey,
            'shops' => $this->shops(),
            'types' => self::TYPES,
            'rewards' => Coupon::active()->where('usage_type', CouponUsage::SurveyReward)->orderBy('name')->get(['id', 'name']),
            // Tras un error de validación vuelven como texto: se normalizan para Alpine
            'questions' => collect(old('questions', $survey->exists
                ? $survey->questions->map(fn ($q) => ['id' => $q->id, 'name' => $q->name, 'type' => $q->type->value,
                    'required_flg' => $q->required_flg, 'options' => implode("\n", $q->options ?? [])])->all()
                : [['id' => null, 'name' => '', 'type' => CustomFieldType::Radio->value, 'required_flg' => 1, 'options' => '']]))
                ->map(fn ($q) => ['id' => $q['id'] ?? null, 'name' => (string) ($q['name'] ?? ''), 'type' => (int) ($q['type'] ?? 8),
                    'required_flg' => (int) ($q['required_flg'] ?? 0), 'options' => (string) ($q['options'] ?? '')])
                ->values()->all(),
            'hasResponses' => $survey->exists && $survey->responses()->exists(),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'coupon_id' => ['nullable', 'exists:coupons,id'],
            'thanks_message' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:0,1'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.name' => ['required', 'string', 'max:500'],
            'questions.*.type' => ['required', Rule::in(array_map(fn ($t) => $t->value, self::TYPES))],
            'questions.*.required_flg' => ['nullable', 'in:0,1'],
            'questions.*.options' => ['nullable', 'string', 'max:5000'],
        ], [
            'questions.required' => 'Añade al menos una pregunta.',
            'questions.*.name.required' => 'Cada pregunta necesita un texto.',
        ], ['name' => 'título', 'ends_on' => 'fecha de fin']);

        foreach ($data['questions'] as $i => $q) {
            if (CustomFieldType::from((int) $q['type'])->hasOptions() && $this->options($q['options'] ?? '') === []) {
                throw ValidationException::withMessages([
                    "questions.{$i}.options" => 'La pregunta «' . $q['name'] . '» necesita opciones (una por línea).',
                ]);
            }
        }

        $questions = $data['questions'];
        unset($data['questions']);
        $data['status'] = (int) ($data['status'] ?? 0);

        return [$data, $questions];
    }

    /** Actualiza, crea y quita preguntas manteniendo los ids: las respuestas se guardan por id */
    private function syncQuestions(Survey $survey, array $questions): void
    {
        $keep = [];

        foreach (array_values($questions) as $i => $q) {
            $type = CustomFieldType::from((int) $q['type']);
            $attributes = [
                'name' => $q['name'],
                'type' => $type,
                'options' => $type->hasOptions() ? $this->options($q['options'] ?? '') : null,
                'required_flg' => (int) ($q['required_flg'] ?? 0),
                'sort' => $i + 1,
            ];

            $question = ! empty($q['id']) ? $survey->questions()->find($q['id']) : null;
            $question ? $question->update($attributes) : $question = $survey->questions()->create($attributes);
            $keep[] = $question->id;
        }

        $survey->questions()->whereNotIn('id', $keep)->delete();
    }

    private function options(string $text): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/', $text)))));
    }

    /** Recuento por opción en las preguntas cerradas; últimas respuestas en las abiertas */
    private function results(Survey $survey): array
    {
        $answers = SurveyResponse::where('survey_id', $survey->id)->pluck('answers');
        $out = [];

        foreach ($survey->questions as $q) {
            $values = $answers->map(fn ($a) => $a[(string) $q->id] ?? null)->filter(fn ($v) => $v !== null && $v !== '' && $v !== []);

            if ($q->type->hasOptions()) {
                $counts = array_fill_keys($q->options ?? [], 0);
                foreach ($values as $v) {
                    foreach ((array) $v as $choice) {
                        $counts[$choice] = ($counts[$choice] ?? 0) + 1;
                    }
                }
                $out[] = ['question' => $q, 'answered' => $values->count(), 'counts' => $counts];
            } elseif ($q->type === CustomFieldType::Numeric) {
                $nums = $values->map(fn ($v) => (float) $v);
                $out[] = ['question' => $q, 'answered' => $values->count(),
                    'stats' => $nums->isEmpty() ? null : ['min' => $nums->min(), 'max' => $nums->max(), 'avg' => round($nums->avg(), 1)]];
            } else {
                $out[] = ['question' => $q, 'answered' => $values->count(), 'samples' => $values->take(-5)->reverse()->values()->all()];
            }
        }

        return $out;
    }
}

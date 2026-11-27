<?php

use App\Models\JobPosting;
use App\Models\SignageMaterial;
use App\Models\SignageProgram;
use App\Services\Terminales\SignageFeedBuilder;

it('genera el XML del programa con los materiales en orden y sin los borrados', function () {
    $program = SignageProgram::factory()->create();
    $first = SignageMaterial::factory()->create(['title' => 'A', 'duration_seconds' => 10]);
    $second = SignageMaterial::factory()->create(['title' => 'B', 'duration_seconds' => 20]);
    $deleted = SignageMaterial::factory()->create(['title' => 'C']);
    $program->materials()->attach([
        $second->id => ['position' => 0],
        $first->id => ['position' => 1],
        $deleted->id => ['position' => 2],
    ]);
    $deleted->delete();

    $xml = app(SignageFeedBuilder::class)->program($program);

    expect($xml)->toBe(
        "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<data>\n"
        ."<p cd=\"{$second->id}\" time=\"20\"  title=\"B\"  type=\"画像ファイル\" pic_extension=\"jpg\" pic_flg=\"1\">B</p>\n"
        ."<p cd=\"{$first->id}\" time=\"10\"  title=\"A\"  type=\"画像ファイル\" pic_extension=\"jpg\" pic_flg=\"1\">A</p>\n"
        ."</data>\n"
    );
});

it('devuelve un floormap vacío mientras shops no tenga floor_name', function () {
    JobPosting::factory()->create();

    expect(app(SignageFeedBuilder::class)->floormap())
        ->toBe("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<data>\n</data>\n");
});

it('migra y siembra los datos de prueba de señalización', function () {
    $this->seed(\Database\Seeders\TerminalesSeeder::class);

    $program = SignageProgram::query()->where('name', '店頭プログラム')->firstOrFail();

    expect($program->materials)->toHaveCount(3)
        ->and($program->displays)->toHaveCount(1)
        ->and(substr_count(app(SignageFeedBuilder::class)->program($program), '<p cd='))->toBe(3);
});

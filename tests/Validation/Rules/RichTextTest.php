<?php

use Emaia\LaravelHotwire\Validation\Rules\RichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RichTextValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['bail', RichText::required(), 'string', RichText::min(3)],
        ];
    }
}

// --- Required ---

it('requires a present non-blank rich text value', function (array $data) {
    $validator = Validator::make($data, [
        'content' => [RichText::required()],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('content'))->toBe('The content field is required.');
})->with([
    'missing' => [[]],
    'null' => [['content' => null]],
    'empty string' => [['content' => '']],
    'empty markup' => [['content' => '<p><br></p>']],
    'non-breaking space' => [['content' => '<p>&nbsp;</p>']],
    'html5 whitespace entities' => [['content' => '<p>&Tab;&NewLine;&ZeroWidthSpace;</p>']],
    'standalone joiners' => [['content' => "<p>\u{200C}\u{200D}</p>"]],
    'empty table' => [['content' => '<table><tbody><tr><td><p></p></td></tr></tbody></table>']],
    'unknown atom wrapper' => [['content' => '<div data-type="widget"></div>']],
]);

it('accepts non-blank rich text as required', function () {
    $validator = Validator::make(
        ['content' => '<p>Publish me</p>'],
        ['content' => [RichText::required()]],
    );

    expect($validator->passes())->toBeTrue();
});

it('accepts recognized non-text content as required', function (string $html) {
    $validator = Validator::make(
        ['content' => $html],
        ['content' => [RichText::required()]],
    );

    expect($validator->passes())->toBeTrue();
})->with([
    'image' => '<p><img src="/storage/photo.png" alt="Team photo"></p>',
    'horizontal rule' => '<hr>',
    'youtube' => '<div data-youtube-video><iframe src="https://www.youtube.com/embed/x"></iframe></div>',
    'audio' => '<audio src="podcast.mp3"></audio>',
    'video' => '<video src="movie.mp4"></video>',
    'svg' => '<svg><path d="M0 0h10v10z"></path></svg>',
    'embed' => '<embed src="document.pdf">',
    'object' => '<object data="document.pdf"></object>',
    'canvas' => '<canvas data-chart="sales"></canvas>',
]);

it('rejects empty countable values as required', function (mixed $value) {
    $validator = Validator::make(
        ['content' => $value],
        ['content' => [RichText::required()]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('content'))->toBe('The content field is required.');
})->with([
    'array' => [[]],
    'countable' => [new ArrayObject],
]);

it('reports the required message before string validation for null content', function () {
    $validator = Validator::make(
        ['content' => null],
        ['content' => ['bail', RichText::required(), 'string']],
    );

    expect($validator->errors()->get('content'))->toBe(['The content field is required.']);
});

it('requires rich text only when the boolean condition is true', function (bool $condition, bool $passes) {
    $validator = Validator::make([], [
        'content' => [RichText::requiredIf($condition)],
    ]);

    expect($validator->passes())->toBe($passes);
})->with([
    'required' => [true, false],
    'optional' => [false, true],
]);

it('evaluates required if closures during validation', function () {
    $evaluations = 0;
    $rule = RichText::requiredIf(function () use (&$evaluations): bool {
        $evaluations++;

        return true;
    });

    expect($evaluations)->toBe(0);

    $validator = Validator::make([], ['content' => [$rule]]);

    expect($validator->fails())->toBeTrue()
        ->and($evaluations)->toBe(1);
});

// --- Length ---

it('validates minimum normalized text length', function (string $html, bool $passes) {
    $validator = Validator::make(
        ['content' => $html],
        ['content' => [RichText::min(5)]],
    );

    expect($validator->passes())->toBe($passes);
})->with([
    'below boundary' => ['<p>Four</p>', false],
    'at boundary' => ['<p>Five!</p>', true],
    'markup is not counted' => ['<p><strong>Four</strong></p>', false],
    'unicode uses mb length' => ['<p>ação!</p>', true],
]);

it('validates maximum normalized text length', function (string $html, bool $passes) {
    $validator = Validator::make(
        ['content' => $html],
        ['content' => [RichText::max(5)]],
    );

    expect($validator->passes())->toBe($passes);
})->with([
    'below boundary' => ['<p>Four</p>', true],
    'at boundary' => ['<p>Five!</p>', true],
    'above boundary' => ['<p>Longer</p>', false],
]);

it('measures recognized non-text content as zero text characters', function () {
    $minimum = Validator::make(
        ['content' => '<div data-youtube-video><iframe src="https://www.youtube.com/embed/x"></iframe></div>'],
        ['content' => [RichText::min(1)]],
    );
    $maximum = Validator::make(
        ['content' => '<svg><path d="M0 0h10v10z"></path></svg>'],
        ['content' => [RichText::max(0)]],
    );

    expect($minimum->fails())->toBeTrue()
        ->and($maximum->passes())->toBeTrue();
});

it('does not spend the text budget on opaque media fallback', function () {
    $maximum = Validator::make(
        ['content' => '<video src="movie.mp4">Your browser does not support video.</video>'],
        ['content' => [RichText::max(0)]],
    );

    expect($maximum->passes())->toBeTrue();
});

it('counts visible svg text toward text limits', function () {
    $minimum = Validator::make(
        ['content' => '<svg><desc>Metadata</desc><text>Hello</text></svg>'],
        ['content' => [RichText::min(5)]],
    );

    expect($minimum->passes())->toBeTrue();
});

it('supports zero length boundaries', function () {
    $minimum = Validator::make(
        ['content' => '<p>Text</p>'],
        ['content' => [RichText::min(0)]],
    );
    $maximum = Validator::make(
        ['content' => '<p>Text</p>'],
        ['content' => [RichText::max(0)]],
    );

    expect($minimum->passes())->toBeTrue()
        ->and($maximum->fails())->toBeTrue();
});

it('uses translated laravel length messages', function () {
    $minimum = Validator::make(
        ['content' => '<p>No</p>'],
        ['content' => [RichText::min(3)]],
    );
    $maximum = Validator::make(
        ['content' => '<p>Too long</p>'],
        ['content' => [RichText::max(3)]],
    );

    expect($minimum->errors()->first('content'))
        ->toBe('The content field must be at least 3 characters.')
        ->and($maximum->errors()->first('content'))
        ->toBe('The content field must not be greater than 3 characters.');
});

it('does not make absent or semantically blank optional content required', function (array $data) {
    $validator = Validator::make($data, [
        'content' => [RichText::min(3), RichText::max(10)],
    ]);

    expect($validator->passes())->toBeTrue();
})->with([
    'missing' => [[]],
    'empty string' => [['content' => '']],
    'blank markup' => [['content' => '<p><br></p>']],
]);

// --- Laravel composition ---

it('composes with nullable', function () {
    $validator = Validator::make(
        ['content' => null],
        ['content' => ['nullable', 'string', RichText::min(3), RichText::max(10)]],
    );

    expect($validator->passes())->toBeTrue();
});

it('lets required rich text take precedence over nullable', function () {
    $validator = Validator::make(
        ['content' => null],
        ['content' => ['nullable', RichText::required()]],
    );

    expect($validator->fails())->toBeTrue();
});

it('composes with sometimes for missing attributes', function () {
    $validator = Validator::make([], [
        'content' => ['sometimes', RichText::required()],
    ]);

    expect($validator->passes())->toBeTrue();
});

it('defers non-string values to laravel string validation', function () {
    $validator = Validator::make(
        ['content' => ['not', 'html']],
        ['content' => ['bail', 'string', RichText::min(3)]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->get('content'))->toHaveCount(1);
});

it('still validates supplied content when required if is false', function () {
    $validator = Validator::make(
        ['content' => '<p>No</p>'],
        ['content' => [RichText::requiredIf(false), RichText::min(3)]],
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects negative length limits', function (string $factory) {
    RichText::{$factory}(-1);
})->with(['min', 'max'])->throws(InvalidArgumentException::class);

it('turns invalid utf-8 into a validation failure', function () {
    $validator = Validator::make(
        ['content' => "invalid\xFF"],
        ['content' => [RichText::min(3)]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('content'))
        ->toBe('The content field must contain valid rich text.');
});

it('uses locale-specific package messages for every rich text constraint', function () {
    $translator = app('translator');
    $previousLocale = app()->getLocale();

    $translator->addLines([
        'validation.rich_text.required' => 'O campo :attribute deve conter conteúdo.',
        'validation.rich_text.min' => 'O campo :attribute deve conter no mínimo :min caracteres.',
        'validation.rich_text.max' => 'O campo :attribute não pode conter mais de :max caracteres.',
        'validation.rich_text.invalid' => 'O campo :attribute deve conter rich text válido.',
    ], 'pt_BR', 'hotwire');

    app()->setLocale('pt_BR');

    try {
        $required = Validator::make([], ['content' => [RichText::required()]]);
        $minimum = Validator::make(['content' => '<p>Oi</p>'], ['content' => [RichText::min(3)]]);
        $maximum = Validator::make(['content' => '<p>Longo</p>'], ['content' => [RichText::max(3)]]);
        $invalid = Validator::make(['content' => "invalid\xFF"], ['content' => [RichText::min(3)]]);

        expect($required->errors()->first('content'))->toBe('O campo content deve conter conteúdo.')
            ->and($minimum->errors()->first('content'))->toBe('O campo content deve conter no mínimo 3 caracteres.')
            ->and($maximum->errors()->first('content'))->toBe('O campo content não pode conter mais de 3 caracteres.')
            ->and($invalid->errors()->first('content'))->toBe('O campo content deve conter rich text válido.');
    } finally {
        app()->setLocale($previousLocale);
    }
});

it('works through form request validation', function () {
    $request = RichTextValidationRequest::create('/', 'POST', [
        'content' => '<p>Valid content</p>',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    expect($request->validated())->toBe(['content' => '<p>Valid content</p>']);
});

it('runs implicit rich text validation through form requests', function () {
    $request = RichTextValidationRequest::create('/', 'POST');
    $request->setContainer(app())->setRedirector(app('redirect'));

    expect(fn () => $request->validateResolved())
        ->toThrow(ValidationException::class, 'The content field is required.');
});

<?php

use Emaia\LaravelHotwire\Components\Attachment\Action;
use Emaia\LaravelHotwire\Components\Button;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\Database\Eloquent\Model;

class FrameTargetRecord extends Model
{
    protected $guarded = [];
}

it('normalizes optional frame targets', function () {
    expect(FrameTarget::normalize(null))->toBeNull()
        ->and(FrameTarget::normalize(false))->toBeNull()
        ->and(FrameTarget::normalize(''))->toBeNull()
        ->and(FrameTarget::normalize('   '))->toBeNull()
        ->and(FrameTarget::normalize(' results '))->toBe('results');
});

it('resolves frame targets from models', function () {
    $model = new FrameTargetRecord;
    $model->id = 42;

    expect(FrameTarget::normalize($model))->toBe('frame_target_record_42');
});

it('rejects a bare true frame target', function () {
    expect(fn () => FrameTarget::normalize(true))
        ->toThrow(InvalidArgumentException::class, 'The frame prop must be a non-empty string or an object resolvable via dom_id().');
});

it('normalizes frame props at component construction boundaries', function () {
    expect((new Button(frame: ' content '))->frame)->toBe('content')
        ->and((new Action(frame: ' preview '))->frame)->toBe('preview');
});

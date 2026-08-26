<?php

use Emaia\LaravelHotwire\Components\Accordion;
use Emaia\LaravelHotwire\Components\AlertDialog;
use Emaia\LaravelHotwire\Components\Carousel;
use Emaia\LaravelHotwire\Components\Drawer;
use Emaia\LaravelHotwire\Components\Dropdown;
use Emaia\LaravelHotwire\Components\FileUpload;
use Emaia\LaravelHotwire\Components\HoverCard;
use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\Components\MultiSelect;
use Emaia\LaravelHotwire\Components\Popover;
use Emaia\LaravelHotwire\Components\ReadMore;
use Emaia\LaravelHotwire\Components\RichText;
use Emaia\LaravelHotwire\Components\Sheet;
use Emaia\LaravelHotwire\Components\Tabs;
use Illuminate\Database\Eloquent\Model;

class StatefulComponentIdRecord extends Model {}

it('derives stateful component ids from a model', function (Closure $make, string $property, string $prefix) {
    $record = new StatefulComponentIdRecord;
    $record->id = 42;
    $component = $make($record);

    expect($component->{$property})->toBe(dom_id($record, $prefix));
})->with([
    'accordion' => [fn (object $id) => new Accordion(id: $id), 'accordionId', 'accordion'],
    'alert dialog' => [fn (object $id) => new AlertDialog(id: $id), 'id', 'alert'],
    'carousel' => [fn (object $id) => new Carousel(id: $id), 'id', 'carousel'],
    'drawer' => [fn (object $id) => new Drawer(id: $id), 'id', 'drawer'],
    'dropdown' => [fn (object $id) => new Dropdown(id: $id), 'id', 'dropdown'],
    'file upload' => [fn (object $id) => new FileUpload(id: $id, url: '/uploads'), 'id', 'file-upload'],
    'hover card' => [fn (object $id) => new HoverCard(id: $id), 'id', 'hover-card'],
    'modal' => [fn (object $id) => new Modal(id: $id), 'id', 'modal'],
    'multi select' => [fn (object $id) => new MultiSelect(id: $id), 'id', 'multi-select'],
    'popover' => [fn (object $id) => new Popover(id: $id), 'id', 'popover'],
    'read more' => [fn (object $id) => new ReadMore(id: $id), 'readMoreId', 'read-more'],
    'rich text' => [fn (object $id) => new RichText(id: $id), 'id', 'rich-text'],
    'sheet' => [fn (object $id) => new Sheet(id: $id), 'id', 'sheet'],
    'tabs' => [fn (object $id) => new Tabs(id: $id), 'tabsId', 'tabs'],
]);

it('passes a model through the Blade id prop', function () {
    $record = new StatefulComponentIdRecord;
    $record->id = 42;
    $html = (string) $this->blade('<x-hw::modal :id="$record">Content</x-hw::modal>', compact('record'));

    expect($html)->toContain('id="'.dom_id($record, 'modal').'"');
});

it('keeps automatic ids unique in one response and stable in a fresh scope', function () {
    $template = '<x-hw::modal>First</x-hw::modal><x-hw::modal>Second</x-hw::modal>';

    app()->forgetScopedInstances();
    $first = (string) $this->blade($template);

    app()->forgetScopedInstances();
    $second = (string) $this->blade($template);

    expect($first)->toContain('id="modal-page-1"', 'id="modal-page-2"')
        ->and($second)->toBe($first);
});

it('preserves the Carousel empty-string id behavior', function () {
    expect((new Carousel(id: ''))->id)->toBe('');
});

it('routes Carousel model ids through the shared stable-key rules', function () {
    $record = new StatefulComponentIdRecord;

    expect(fn () => new Carousel(id: $record))
        ->toThrow(InvalidArgumentException::class, 'Component id models must have a stable key.');

    $record->id = 0;

    expect((new Carousel(id: $record))->id)
        ->toBe(dom_class($record, 'carousel').'_0');
});

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class RichText extends Component
{
    use StripsNullProps;

    private const BASIC_TOOLBAR = [
        'bold',
        'italic',
        'link',
        'bullet-list',
        'ordered-list',
    ];

    private const CLASSIC_TOOLBAR = [
        'bold',
        'italic',
        'underline',
        'strike',
        'code',
        'heading-1',
        'heading-2',
        'heading-3',
        'link',
        'bullet-list',
        'ordered-list',
        'blockquote',
        'code-block',
        'horizontal-rule',
        'undo',
        'redo',
    ];

    private const TOOLBAR_BUTTONS = [
        'bold' => ['action' => 'bold', 'target' => 'bold', 'label' => 'Bold', 'icon' => 'bold'],
        'italic' => ['action' => 'italic', 'target' => 'italic', 'label' => 'Italic', 'icon' => 'italic'],
        'underline' => ['action' => 'underline', 'target' => 'underline', 'label' => 'Underline', 'icon' => 'underline'],
        'strike' => ['action' => 'strike', 'target' => 'strike', 'label' => 'Strike', 'icon' => 'strikethrough'],
        'code' => ['action' => 'code', 'target' => 'code', 'label' => 'Inline code', 'icon' => 'code'],
        'heading-1' => ['action' => 'heading', 'target' => 'heading', 'label' => 'Heading 1', 'icon' => 'heading-1', 'level' => 1],
        'heading-2' => ['action' => 'heading', 'target' => 'heading', 'label' => 'Heading 2', 'icon' => 'heading-2', 'level' => 2],
        'heading-3' => ['action' => 'heading', 'target' => 'heading', 'label' => 'Heading 3', 'icon' => 'heading-3', 'level' => 3],
        'link' => ['action' => 'link', 'target' => 'link', 'label' => 'Link', 'icon' => 'link'],
        'bullet-list' => ['action' => 'bulletList', 'target' => 'bulletList', 'label' => 'Bullet list', 'icon' => 'list'],
        'ordered-list' => ['action' => 'orderedList', 'target' => 'orderedList', 'label' => 'Numbered list', 'icon' => 'list-ordered'],
        'blockquote' => ['action' => 'blockquote', 'target' => 'blockquote', 'label' => 'Quote', 'icon' => 'quote'],
        'code-block' => ['action' => 'codeBlock', 'target' => 'codeBlock', 'label' => 'Code block', 'icon' => 'code-xml'],
        'horizontal-rule' => ['action' => 'horizontalRule', 'target' => null, 'label' => 'Horizontal rule', 'icon' => 'minus'],
        'undo' => ['action' => 'undo', 'target' => null, 'label' => 'Undo', 'icon' => 'undo-2'],
        'redo' => ['action' => 'redo', 'target' => null, 'label' => 'Redo', 'icon' => 'redo-2'],
    ];

    public string $identifier;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public ?string $errorKey = null,
        public ?string $placeholder = null,
        public bool $editable = true,
        public string $output = 'html',
        public bool|string|array|null $toolbar = true,
        public bool $imageUpload = false,
        public bool $old = true,
        public string $class = '',
        public string $inputClass = '',
        public string $editorClass = '',
        public string $controller = 'rich-text',
        public ?Htmlable $stimulus = null,
    ) {
        $this->identifier = $this->controller;
    }

    public function render()
    {
        return view('hotwire::component-views.rich-text');
    }

    /**
     * @return array<int, array{action: string, target: ?string, label: string, icon: string, level?: int}>
     */
    public function toolbarButtons(): array
    {
        return array_values(array_filter(
            array_map(
                fn (string $button): ?array => self::TOOLBAR_BUTTONS[$button] ?? null,
                $this->toolbarButtonKeys(),
            )
        ));
    }

    /**
     * @return string[]
     */
    private function toolbarButtonKeys(): array
    {
        if ($this->toolbar === false) {
            return [];
        }

        if ($this->toolbar === true || $this->toolbar === null || $this->toolbar === 'basic') {
            return self::BASIC_TOOLBAR;
        }

        if ($this->toolbar === 'classic') {
            return self::CLASSIC_TOOLBAR;
        }

        $requested = is_array($this->toolbar)
            ? $this->toolbar
            : preg_split('/[\s,|]+/', $this->toolbar, flags: PREG_SPLIT_NO_EMPTY);

        if (! is_array($requested)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $button): string => is_string($button) ? $button : '', $requested),
            fn (string $button): bool => array_key_exists($button, self::TOOLBAR_BUTTONS),
        ));
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);
        $data['toolbarButtons'] = $this->toolbarButtons(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        bool $required,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';

        $resolvedId = $id !== null && $id !== ''
            ? $id
            : ($hasName ? FieldKey::toId($name) : 'hw-rich-text-'.uniqid());

        $resolvedErrorKey = $errorKey !== null && $errorKey !== ''
            ? $errorKey
            : ($hasName ? FieldKey::toErrorKey($name) : '');

        $initial = $this->value === null ? '' : (string) $this->value;
        $resolvedValue = $this->old && $resolvedErrorKey !== ''
            ? (string) old($resolvedErrorKey, $initial)
            : $initial;

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'resolvedValue' => $resolvedValue,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'dataController' => $this->identifier,
        ];
    }
}

# Multi-stage forms

Wizards built on a persistent draft model and a single Turbo Frame. Each step is a normal Laravel
form — validation, `old()`, error bags, FormRequests all work the way you already know. State lives
in the database; the client owns nothing.

## The pattern

1. User starts the wizard → server creates a `status=draft` row → redirect to step 1's URL.
2. The page renders the wizard chrome (progress indicator + step content) inside a single
   `<turbo-frame id="wizard">`.
3. Each step's form posts back, server validates **only that step's fields**, persists them on the
   draft, and returns the next step's view inside the same frame.
4. Validation failure returns the same step inside the frame — errors render in place, no full page
   reload, no lost input.
5. The final step flips `status=published` and redirects to the resource's real URL.

The whole wizard is a sequence of frame swaps over a single draft model. No session juggling, no
client-side state, no JS step-toggling.

## When to use this

- Long forms with abandonment risk — steps reduce perceived complexity.
- Stages with different concerns (basics → details → permissions → review).
- Conditional branching: step 3 depends on what was picked in step 2.
- "Resume where you left off" is valuable.

### When NOT to use

- 2–4 fields total — just one form.
- Anonymous flows with no user account to attach the draft to. Use session-based wizards (or require
  signup as step 1).
- Steps that don't reduce abandonment and aren't conditional — splitting hurts conversion.

## Setup

Concrete example: a 3-step **job posting** wizard — basics → description → compensation, then
publish.

### 1. The draft model

```php
// database/migrations/..._create_job_postings_table.php
Schema::create('job_postings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->enum('status', ['draft', 'published'])->default('draft');

    $table->string('title')->nullable();
    $table->string('department')->nullable();
    $table->text('description')->nullable();
    $table->unsignedInteger('salary_min')->nullable();
    $table->unsignedInteger('salary_max')->nullable();

    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

Fields are nullable while the row is a draft. Publish-time validation enforces presence; per-step
validation enforces format. Alternative: a separate `job_posting_drafts` table — useful if drafts
shouldn't share queries/scopes with published rows.

### 2. Routes

```php
Route::post('jobs', [JobPostingController::class, 'start'])->name('jobs.start');
Route::get('jobs/{job}/edit', [JobPostingController::class, 'edit'])->name('jobs.edit');
Route::patch('jobs/{job}/{step}', [JobPostingController::class, 'updateStep'])
    ->whereIn('step', ['basics', 'description', 'compensation'])
    ->name('jobs.update-step');
Route::post('jobs/{job}/publish', [JobPostingController::class, 'publish'])->name('jobs.publish');
```

The `step` segment is part of the URL — refresh-safe, bookmarkable, browser back works between
steps for free.

### 3. Start, resume, advance

```php
class JobPostingController
{
    public function start(Request $request)
    {
        $job = $request->user()->jobPostings()->create();

        return redirect()->route('jobs.edit', ['job' => $job, 'step' => 'basics']);
    }

    public function edit(JobPosting $job, ?string $step = null)
    {
        $this->authorize('update', $job);

        $step ??= $this->resumeStep($job);

        return view("jobs.wizard.{$step}", compact('job'));
    }

    public function updateStep(UpdateJobStepRequest $request, JobPosting $job, string $step)
    {
        $job->update($request->validated());

        return redirect()->route('jobs.edit', [
            'job' => $job,
            'step' => $this->nextStep($step),
        ]);
    }

    private function resumeStep(JobPosting $job): string
    {
        return match (true) {
            blank($job->title) => 'basics',
            blank($job->description) => 'description',
            default => 'compensation',
        };
    }

    private function nextStep(string $current): string
    {
        return match ($current) {
            'basics' => 'description',
            'description' => 'compensation',
            'compensation' => 'review',
        };
    }
}
```

`updateStep` returns a redirect; Turbo Drive follows it and the frame swaps to the next step's
view. Validation errors short-circuit the redirect and re-render the same step inside the frame.

### 4. Per-step validation

One FormRequest, dispatching by `route('step')`:

```php
class UpdateJobStepRequest extends FormRequest
{
    public function rules(): array
    {
        return match ($this->route('step')) {
            'basics' => [
                'title' => ['required', 'string', 'max:120'],
                'department' => ['required', 'string'],
            ],
            'description' => [
                'description' => ['required', 'string', 'min:50'],
            ],
            'compensation' => [
                'salary_min' => ['required', 'integer', 'min:0'],
                'salary_max' => ['required', 'integer', 'gte:salary_min'],
            ],
        };
    }
}
```

Each step validates only its own fields. The draft can stay incomplete between steps without
tripping required rules from later steps.

### 5. The wizard layout

```blade
{{-- resources/views/components/layouts/wizard-base.blade.php --}}
@props(['job', 'currentStep'])

@if (request()->wasFromTurboFrame('wizard'))
    <turbo-frame id="wizard">
        <x-wizard-progress :current="$currentStep" :job="$job" />
        {{ $slot }}
    </turbo-frame>
@else
    <x-layouts.dashboard>
        <turbo-frame id="wizard">
            <x-wizard-progress :current="$currentStep" :job="$job" />
            {{ $slot }}
        </turbo-frame>
    </x-layouts.dashboard>
@endif
```

The progress indicator lives **inside** the frame so it updates with every step swap. Same
[frame-or-page](./frame-or-page.md) trick: each step has a real URL and renders standalone on
direct navigation.

### 6. A step view

```blade
{{-- resources/views/jobs/wizard/basics.blade.php --}}
<x-layouts.wizard-base :job="$job" current-step="basics">
    <form method="POST" action="{{ route('jobs.update-step', ['job' => $job, 'step' => 'basics']) }}">
        @csrf
        @method('PATCH')

        <label>
            Title
            <input type="text" name="title" value="{{ old('title', $job->title) }}">
            @error('title') <span>{{ $message }}</span> @enderror
        </label>

        <label>
            Department
            <input type="text" name="department" value="{{ old('department', $job->department) }}">
            @error('department') <span>{{ $message }}</span> @enderror
        </label>

        <button type="submit">Continue</button>
    </form>
</x-layouts.wizard-base>
```

Nothing wizard-specific in the form itself — it's a plain Laravel form posting to a route. The
"wizard-ness" is entirely in the layout (frame wrapper + progress) and the controller (next-step
routing).

### 7. Review and publish

```blade
{{-- resources/views/jobs/wizard/review.blade.php --}}
<x-layouts.wizard-base :job="$job" current-step="review">
    <h2>Review</h2>

    <dl>
        <dt>Title</dt><dd>{{ $job->title }}</dd>
        <dt>Department</dt><dd>{{ $job->department }}</dd>
        {{-- ... --}}
    </dl>

    <a href="{{ route('jobs.edit', ['job' => $job, 'step' => 'basics']) }}">Edit basics</a>

    <form method="POST" action="{{ route('jobs.publish', $job) }}">
        @csrf
        <button type="submit">Publish</button>
    </form>
</x-layouts.wizard-base>
```

```php
public function publish(PublishJobRequest $request, JobPosting $job)
{
    $job->update([
        'status' => 'published',
        'published_at' => now(),
    ]);

    return redirect()->route('jobs.show', $job);
}
```

`PublishJobRequest` runs the **full** ruleset (all required fields, cross-step constraints). If
publish-time validation fails, redirect back to the failing step:

```php
public function publish(Request $request, JobPosting $job)
{
    try {
        app(PublishJobRequest::class);
    } catch (ValidationException $e) {
        return redirect()->route('jobs.edit', [
            'job' => $job,
            'step' => $this->stepForFields(array_keys($e->errors())),
        ])->withErrors($e->errors());
    }

    // ... publish
}
```

## Variants

### Conditional branching

Branch on what's already on the draft:

```php
private function nextStep(JobPosting $job, string $current): string
{
    return match ($current) {
        'basics' => $job->department === 'engineering' ? 'tech-stack' : 'description',
        'tech-stack' => 'description',
        'description' => 'compensation',
        'compensation' => 'review',
    };
}
```

The progress indicator should reflect the active branch — pass the resolved step list from the
controller, not a hard-coded one.

### Save & exit

Every step is already saved on `Continue`. For an explicit "Save & exit" button, post the same form
and redirect to the dashboard:

```blade
<button type="submit" name="action" value="exit">Save & exit</button>
```

```php
if ($request->input('action') === 'exit') {
    return redirect()->route('dashboard');
}
```

The user returns later via `GET /jobs/{job}/edit` — `resumeStep()` lands them on the first
incomplete step.

### Abandoned-draft cleanup

Drafts that never publish accumulate. A scheduled command:

```php
// app/Console/Commands/PruneAbandonedDrafts.php
JobPosting::where('status', 'draft')
    ->where('updated_at', '<', now()->subDays(30))
    ->delete();
```

Tune the window to your domain. For high-stakes drafts (legal, contracts), prefer archiving over
deleting.

## Trade-offs

- **Schema gets nullable fields** (or a parallel draft table). Pick based on whether drafts and
  published rows share queries.
- **Validation lives in two places** — per-step rules and publish-time rules. Keeping them in one
  FormRequest with a `match` on `step` (and a `'publish'` case) helps.
- **Drafts need cleanup.** Add a pruning job from day one.
- **Authorization runs on every step.** Centralize it in the controller's `__construct` or a
  `middleware('can:update,job')` route group.

## What this recipe doesn't ship

There is no `<x-hwc::wizard>` component. The shape of a wizard varies enough (linear vs branching,
draft vs session, validate-as-you-go vs at-the-end, custom progress UI) that a generic component
would either be too rigid or too configurable to be useful. The Turbo Frame + draft model gives you
90% of the value with primitives you already have.

If you want a small UX nicety — like a Stimulus controller that handles "Are you sure?" on a back
button when there are unsaved changes — build it for your app, not as a wizard abstraction.

## See also

- [Frame-or-page views](./frame-or-page.md) — the dual-mode layout pattern this recipe extends.
- [Server-driven confirmation](./server-driven-confirmation.md) — same "server paints the next view"
  spirit, applied to destructive actions.
- [Composing streams](./composing-streams.md) — for non-wizard responses that need to fire multiple
  UI updates at once.

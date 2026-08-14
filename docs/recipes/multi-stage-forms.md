# Multi-stage forms

Wizards built on a persistent draft model and a single Turbo Frame. Each step is a normal Laravel
form — validation, `old()`, error bags, FormRequests all work the way you already know. State lives
in the database; the client owns nothing.

## The pattern

1. User starts the wizard → server creates a `status=draft` row → redirect to step 1's URL.
2. The page renders the wizard chrome (progress indicator + step content) inside a single
   `<turbo-frame id="wizard">` that promotes step navigation to browser history.
3. Each step's form posts back, server validates **only that step's fields**, persists them on the
   draft, and redirects the frame to the next step.
4. Validation failure redirects to the exact GET URL that rendered the step, so errors return to the
   frame with no lost input.
5. The final step flips `status=published` and targets `_top`, leaving the wizard frame for the
   resource's real URL.

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
Route::middleware('auth')->group(function () {
    Route::post('jobs', [JobPostingController::class, 'start'])->name('jobs.start');
    Route::get('jobs/{job}/edit/{step?}', [JobPostingController::class, 'edit'])
        ->whereIn('step', ['basics', 'description', 'compensation', 'review'])
        ->name('jobs.edit');
    Route::patch('jobs/{job}/{step}', [JobPostingController::class, 'updateStep'])
        ->whereIn('step', ['basics', 'description', 'compensation'])
        ->name('jobs.update-step');
    Route::post('jobs/{job}/publish', [JobPostingController::class, 'publish'])->name('jobs.publish');
});
```

All wizard routes require an authenticated user. The optional `step` segment gives each step a real
URL. Omitting it resumes the first incomplete step; passing it opens that step directly.

### 3. Start, resume, advance

```php
use Illuminate\Support\Facades\Gate;

class JobPostingController
{
    public function start(Request $request)
    {
        $job = $request->user()->jobPostings()->create();

        return redirect()->route('jobs.edit', ['job' => $job, 'step' => 'basics']);
    }

    public function edit(JobPosting $job, ?string $step = null)
    {
        Gate::authorize('update', $job);

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
            $job->salary_min === null || $job->salary_max === null => 'compensation',
            default => 'review',
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

`updateStep` redirects to the next step's GET URL. The frame host configured below follows that
redirect and records the resulting URL in browser history. `edit` authorizes the bound job with the
`update` policy; the mutation requests below enforce the same ability before their controller
methods run.

### 4. Per-step validation

Extend `TurboFormRequest` and dispatch rules by the route segment:

```php
use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;

class UpdateJobStepRequest extends TurboFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('job'));
    }

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
tripping required rules from later steps. Paired with `track-frame-src` on the form, this request
redirects failures to the exact step URL that rendered the frame rather than relying on session
history.

### 5. The wizard layout

```blade
{{-- resources/views/components/layouts/wizard.blade.php --}}
<x-layouts.dashboard>
    <hw:frame id="wizard" advance>
        {{ $slot }}
    </hw:frame>
</x-layouts.dashboard>
```

This layout owns the frame host for direct navigation. `advance` promotes successful frame
navigations to browser history, so Back and Forward revisit step URLs. Do not add another wizard
frame to the shared dashboard layout.

### 6. A step view

```blade
{{-- resources/views/jobs/wizard/basics.blade.php --}}
<hw:frame-or-page frame="wizard" layout="layouts.wizard">
    <x-wizard-progress current="basics" :job="$job" />

    <hw:form
        :action="route('jobs.update-step', ['job' => $job, 'step' => 'basics'])"
        method="patch"
        track-frame-src
    >

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
    </hw:form>
</hw:frame-or-page>
```

On a `Turbo-Frame: wizard` request, `<hw:frame-or-page>` returns the matching frame payload and
skips the page layout. On a direct visit or refresh, it renders `layouts.wizard`, whose single frame
host wraps the same content. The progress indicator stays inside the swapped content in both cases.

`<hw:form>` supplies CSRF and method spoofing. `track-frame-src` adds the current step URL as
`_turbo_frame_src`, which `UpdateJobStepRequest` uses for deterministic validation redirects.

### 7. Review and publish

```blade
{{-- resources/views/jobs/wizard/review.blade.php --}}
<hw:frame-or-page frame="wizard" layout="layouts.wizard">
    <x-wizard-progress current="review" :job="$job" />

    <h2>Review</h2>

    <dl>
        <dt>Title</dt>
        <dd>{{ $job->title }}</dd>
        <dt>Department</dt>
        <dd>{{ $job->department }}</dd>
        {{-- ... --}}
    </dl>

    <a href="{{ route('jobs.edit', ['job' => $job, 'step' => 'basics']) }}">Edit basics</a>

    <hw:form :action="route('jobs.publish', $job)" method="post" frame="_top">
        <button type="submit">Publish</button>
    </hw:form>
</hw:frame-or-page>
```

```php
use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;

class PublishJobRequest extends TurboFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('job'));
    }

    protected function prepareForValidation(): void
    {
        $this->redirect = route('jobs.edit', [
            'job' => $this->route('job'),
            'step' => 'review',
        ]);
    }

    public function validationData(): array
    {
        return $this->route('job')->only([
            'title',
            'department',
            'description',
            'salary_min',
            'salary_max',
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'department' => ['required', 'string'],
            'description' => ['required', 'string', 'min:50'],
            'salary_min' => ['required', 'integer', 'min:0'],
            'salary_max' => ['required', 'integer', 'gte:salary_min'],
        ];
    }
}

public function publish(PublishJobRequest $request, JobPosting $job)
{
    $job->update([
        'status' => 'published',
        'published_at' => now(),
    ]);

    return redirect()->route('jobs.show', $job);
}
```

`PublishJobRequest` validates the persisted draft rather than the review form's empty payload. Its
explicit review URL makes validation redirects deterministic. Because the form targets `_top`, both
validation and successful redirects are full-page navigations; the successful publish cannot be
trapped inside `wizard`. Render a validation summary on the review view with links to the relevant
step URLs.

## Variants

### Conditional branching

The base controller above uses `$this->nextStep($step)`. When branching depends on draft state,
change both the caller and method signature:

```php
return redirect()->route('jobs.edit', [
    'job' => $job,
    'step' => $this->nextStep($job, $step),
]);

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
controller, not a hard-coded one. Add every branch step to the GET route constraint and the
per-step request rules.

### Save & exit

Every step is already saved on `Continue`. For an explicit "Save & exit" button, post the same form
and redirect to the dashboard:

```blade
<button type="submit" name="action" value="exit" data-turbo-frame="_top">Save & exit</button>
```

```php
if ($request->string('action')->is('exit')) {
    return redirect()->route('dashboard');
}
```

The submitter's `_top` target lets the dashboard redirect replace the page instead of looking for a
`wizard` frame in the response.

The user returns later via `GET /jobs/{job}/edit` — the omitted optional segment lets `resumeStep()`
land them on the first incomplete step.

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
  rules object or shared rule methods avoids drift.
- **Drafts need cleanup.** Add a pruning job from day one.
- **Authorization is enforced at every entry point.** The `auth` middleware guards the routes,
  `edit` checks the bound job in the controller, and both mutation FormRequests check it before
  validation and controller execution.

## What this recipe doesn't ship

There is no `<hw:wizard>` component. The shape of a wizard varies enough (linear vs branching,
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

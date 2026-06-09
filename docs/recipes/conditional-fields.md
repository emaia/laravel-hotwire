# Conditional fields

Five real-world form patterns built on the `conditional-fields` controller plus the
`<x-hwc::conditional-field>` component. Each example puts `data-controller="conditional-fields"` on
the form and lets the component handle the rest — single source of truth for every show/hide
rule, no client/server drift.

## Pattern 1 — "Other" reason (single select, OR + equality)

A feedback form with a `reason` select. Some reasons need a free-text follow-up; others reveal a
"details" textarea.

```blade
<form data-controller="conditional-fields" action="/feedback" method="POST">
    @csrf

    <x-hwc::field name="reason" label="What's this about?">
        <x-hwc::select name="reason">
            <option value="">Pick one…</option>
            <option value="bug">Bug</option>
            <option value="feature">Feature request</option>
            <option value="question">Question</option>
            <option value="other">Other</option>
        </x-hwc::select>
    </x-hwc::field>

    <x-hwc::conditional-field :when="['reason' => ['bug', 'feature']]">
        <x-hwc::field name="details" label="What happened (or what's missing)?">
            <x-hwc::textarea name="details" rows="4">{{ old('details') }}</x-hwc::textarea>
        </x-hwc::field>
    </x-hwc::conditional-field>

    <x-hwc::conditional-field :when="['reason' => 'other']">
        <x-hwc::field name="other_reason" label="Tell us">
            <x-hwc::input name="other_reason" value="{{ old('other_reason') }}"/>
        </x-hwc::field>
    </x-hwc::conditional-field>

    <button type="submit">Send</button>
</form>
```

## Pattern 2 — Ship to a different address (boolean checkbox, fieldset cascade)

A checkout form where a single checkbox reveals an entire shipping address block. The `<fieldset>`
cascade handles the disable for free.

```blade
<form data-controller="conditional-fields" action="/checkout" method="POST">
    @csrf

    <fieldset>
        <legend>Billing address</legend>
        <x-hwc::input name="billing_address" value="{{ old('billing_address') }}"/>
        <x-hwc::input name="billing_city" value="{{ old('billing_city') }}"/>
        <x-hwc::input name="billing_zip" value="{{ old('billing_zip') }}"/>
    </fieldset>

    <label class="my-4 flex items-center gap-2">
        <input type="checkbox" name="ship_different" value="1" @checked(old('ship_different'))/>
        Ship to a different address
    </label>

    <x-hwc::conditional-field :when="['ship_different' => ':checked']">
        <legend>Shipping address</legend>
        <x-hwc::input name="shipping_address" value="{{ old('shipping_address') }}"/>
        <x-hwc::input name="shipping_city" value="{{ old('shipping_city') }}"/>
        <x-hwc::input name="shipping_zip" value="{{ old('shipping_zip') }}"/>
    </x-hwc::conditional-field>

    <button type="submit">Continue to payment</button>
</form>
```

## Pattern 3 — Subscription tiers (radio with multi-value OR)

Plan picker that reveals "team size" for Pro and Enterprise, and a second block of fields only
for Enterprise.

```blade
<form data-controller="conditional-fields" action="/subscribe" method="POST">
    @csrf

    <fieldset>
        <legend>Plan</legend>
        @foreach (['starter' => 'Starter (1 user)',
                   'pro' => 'Pro (up to 10 users)',
                   'enterprise' => 'Enterprise (unlimited)'] as $value => $label)
            <label>
                <input type="radio" name="plan" value="{{ $value }}"
                       @checked(old('plan', 'starter') === $value)/>
                {{ $label }}
            </label>
        @endforeach
    </fieldset>

    <x-hwc::conditional-field :when="['plan' => ['pro', 'enterprise']]">
        <x-hwc::field name="team_size" label="How many seats?">
            <x-hwc::input type="number" name="team_size" min="1" max="500"
                          value="{{ old('team_size') }}"/>
        </x-hwc::field>
    </x-hwc::conditional-field>

    <x-hwc::conditional-field :when="['plan' => 'enterprise']">
        <legend>Enterprise</legend>
        <x-hwc::field name="sla_contact" label="Primary contact for SLA negotiation">
            <x-hwc::input name="sla_contact" type="email" value="{{ old('sla_contact') }}"/>
        </x-hwc::field>
        <x-hwc::field name="annual_volume" label="Estimated annual API volume">
            <x-hwc::input type="number" name="annual_volume" value="{{ old('annual_volume') }}"/>
        </x-hwc::field>
    </x-hwc::conditional-field>
</form>
```

## Pattern 4 — NPS survey (numeric radio with score-bucket follow-ups)

Reveal different follow-up questions for detractors vs. promoters by listing the relevant scores
in a single OR rule.

```blade
<form data-controller="conditional-fields" action="/survey" method="POST">
    @csrf

    <fieldset>
        <legend>How likely are you to recommend us?</legend>
        @foreach (range(0, 10) as $n)
            <label>
                <input type="radio" name="score" value="{{ $n }}" @checked((int) old('score') === $n)/>
                {{ $n }}
            </label>
        @endforeach
    </fieldset>

    <x-hwc::conditional-field :when="['score' => ['0', '1', '2', '3', '4', '5', '6']]">
        <x-hwc::field name="reason_low" label="What's the main reason for that score?">
            <x-hwc::textarea name="reason_low" rows="3">{{ old('reason_low') }}</x-hwc::textarea>
        </x-hwc::field>
    </x-hwc::conditional-field>

    <x-hwc::conditional-field :when="['score' => ['9', '10']]">
        <x-hwc::field name="reason_high" label="What's the main reason for that score?">
            <x-hwc::textarea name="reason_high" rows="3">{{ old('reason_high') }}</x-hwc::textarea>
        </x-hwc::field>
    </x-hwc::conditional-field>
</form>
```

> Note: HTML form values are strings, so the rule reads `'0'` through `'10'`, not the integers.

## Pattern 5 — Newsletter preferences (checkbox group `name[]` + AND between triggers)

The user picks any combination of interests. The cadence selector appears when at least one
"live" interest is picked; the webinar reminders sub-checkbox appears only when "events" is in
the group.

```blade
<form data-controller="conditional-fields" action="/preferences" method="POST">
    @csrf

    <fieldset>
        <legend>I'm interested in:</legend>
        @foreach (['news' => 'Product news',
                   'tips' => 'Tips & tutorials',
                   'events' => 'Events & webinars',
                   'research' => 'Research & reports'] as $value => $label)
            <label>
                <input type="checkbox" name="interests[]" value="{{ $value }}"
                       @checked(in_array($value, old('interests', [])))/>
                {{ $label }}
            </label>
        @endforeach
    </fieldset>

    <x-hwc::conditional-field :when="['interests' => ['news', 'tips', 'events']]">
        <x-hwc::field name="cadence" label="How often?">
            <x-hwc::select name="cadence">
                <option value="weekly" @selected(old('cadence') === 'weekly')>Weekly</option>
                <option value="monthly" @selected(old('cadence') === 'monthly')>Monthly</option>
            </x-hwc::select>
        </x-hwc::field>
    </x-hwc::conditional-field>

    <x-hwc::conditional-field :when="['interests' => 'events']">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="webinar_reminders" value="1"
                   @checked(old('webinar_reminders'))/>
            Send me reminders 24h before each event
        </label>
    </x-hwc::conditional-field>
</form>
```

## Edit-form pattern — the `state` prop

When the initial value comes from a model rather than the request, pass a `state` array so the
component knows what the trigger value is for the first paint. Compute it once at the top of the
form and reuse across every dependent:

```blade
@php $state = [
    'reason' => old('reason', $message->reason),
    'ship_different' => old('ship_different', $message->ship_different),
]; @endphp

<form data-controller="conditional-fields" action="/messages/{{ $message->id }}" method="POST">
    @csrf @method('PATCH')

    <x-hwc::select name="reason">
        @foreach ($reasons as $value => $label)
            <option value="{{ $value }}" @selected($state['reason'] === $value)>{{ $label }}</option>
        @endforeach
    </x-hwc::select>

    <x-hwc::conditional-field :when="['reason' => 'other']" :state="$state">
        <x-hwc::input name="other_reason"
                      value="{{ old('other_reason', $message->other_reason) }}"/>
    </x-hwc::conditional-field>
</form>
```

## See also

- [Conditional fields controller](../controllers/conditional-fields.md) — full rule grammar reference.
- [`<x-hwc::conditional-field>` component](../components/conditional-field.md) — props and edge cases.

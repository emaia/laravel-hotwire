# Conditional fields

Five real-world form patterns built on the `conditional-fields` controller plus the
`<hw:conditional-field>` component. Each example puts `data-controller="conditional-fields"` on
the form and lets the component handle the rest — single source of truth for every show/hide
rule, no client/server drift.

## Pattern 1 — "Other" reason (single select, OR + equality)

A feedback form with a `reason` select. Some reasons need a free-text follow-up; others reveal a
"details" textarea.

```blade
<form data-controller="conditional-fields" action="/feedback" method="POST">
    @csrf

    <hw:field name="reason" label="What's this about?">
        <hw:select
            name="reason"
            placeholder="Pick one…"
            :options="[
                'bug'      => 'Bug',
                'feature'  => 'Feature request',
                'question' => 'Question',
                'other'    => 'Other',
            ]"
        />
    </hw:field>

    <hw:conditional-field :when="['reason' => ['bug', 'feature']]">
        <hw:field name="details" label="What happened (or what's missing)?">
            <hw:textarea name="details" rows="4" />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field :when="['reason' => 'other']">
        <hw:field name="other_reason" label="Tell us">
            <hw:input name="other_reason" />
        </hw:field>
    </hw:conditional-field>

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
        <hw:input name="billing_address"/>
        <hw:input name="billing_city"/>
        <hw:input name="billing_zip"/>
    </fieldset>

    <label class="my-4 flex items-center gap-2">
        <input type="checkbox" name="ship_different" value="1" @checked(old('ship_different'))/>
        Ship to a different address
    </label>

    <hw:conditional-field :when="['ship_different' => ':checked']">
        <legend>Shipping address</legend>
        <hw:input name="shipping_address"/>
        <hw:input name="shipping_city"/>
        <hw:input name="shipping_zip"/>
    </hw:conditional-field>

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

    <hw:conditional-field :when="['plan' => ['pro', 'enterprise']]">
        <hw:field name="team_size" label="How many seats?">
            <hw:input type="number" name="team_size" min="1" max="500" />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field :when="['plan' => 'enterprise']">
        <legend>Enterprise</legend>
        <hw:field name="sla_contact" label="Primary contact for SLA negotiation">
            <hw:input name="sla_contact" type="email"/>
        </hw:field>
        <hw:field name="annual_volume" label="Estimated annual API volume">
            <hw:input type="number" name="annual_volume"/>
        </hw:field>
    </hw:conditional-field>
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

    <hw:conditional-field :when="['score' => ['0', '1', '2', '3', '4', '5', '6']]">
        <hw:field name="reason_low" label="What's the main reason for that score?">
            <hw:textarea name="reason_low" rows="3" />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field :when="['score' => ['9', '10']]">
        <hw:field name="reason_high" label="What's the main reason for that score?">
            <hw:textarea name="reason_high" rows="3" />
        </hw:field>
    </hw:conditional-field>
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

    <hw:conditional-field :when="['interests' => ['news', 'tips', 'events']]">
        <hw:field name="cadence" label="How often?">
            <hw:select
                name="cadence"
                :options="['weekly' => 'Weekly', 'monthly' => 'Monthly']"
            />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field :when="['interests' => 'events']">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="webinar_reminders" value="1"
                   @checked(old('webinar_reminders'))/>
            Send me reminders 24h before each event
        </label>
    </hw:conditional-field>
</form>
```

## Edit-form pattern — the `model` prop

`<hw:input>`, `<hw:select>`, and `<hw:textarea>` already merge `old()` with the
`value` / `selected` prop. Pass the same model to `<hw:conditional-field>` and it evaluates
`old(field, $model->field)` — the same lookup those fields use internally. Validation retries
always win over the model fallback.

```blade
<form data-controller="conditional-fields" action="/messages/{{ $message->id }}" method="POST">
    @csrf @method('PATCH')

    <hw:select
        name="reason"
        :options="$reasons"
        :selected="$message->reason"
    />

    <hw:conditional-field :model="$message" :when="['reason' => 'other']">
        <hw:input name="other_reason" :value="$message->other_reason" />
    </hw:conditional-field>
</form>
```

No `@php` state map, no parallel structures — `<hw:conditional-field>` reads the same
`old()` lookup the inputs use internally.

## See also

- [Conditional fields controller](../controllers/conditional-fields.md) — full rule grammar reference.
- [`<hw:conditional-field>` component](../components/conditional-field.md) — props and edge cases.

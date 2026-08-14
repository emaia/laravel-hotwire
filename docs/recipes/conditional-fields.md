# Conditional fields

Add `conditional-fields` to `<hw:form>`, then put each rule on `<hw:conditional-field>`. The component uses the same rule
for server-rendered visibility and client-side updates, and hidden fieldsets are disabled so their values are not
submitted.

## Ask for details by reason

Use `|` to match any value for one field:

```blade
<hw:form conditional-fields :action="route('feedback.store')" method="post">
    <hw:field name="reason" label="What's this about?">
        <hw:select
            :options="[
                'bug' => 'Bug',
                'feature' => 'Feature request',
                'question' => 'Question',
                'other' => 'Other',
            ]"
            placeholder="Pick one..."
        />
    </hw:field>

    <hw:conditional-field when="reason=bug|feature">
        <hw:field name="details" label="What happened, or what's missing?">
            <hw:textarea rows="4" />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field when="reason=other">
        <hw:field name="other_reason" label="Tell us more">
            <hw:input />
        </hw:field>
    </hw:conditional-field>

    <hw:button type="submit">Send</hw:button>
</hw:form>
```

## Reveal a shipping address

Use `:checked` for a boolean checkbox. The conditional field's default `<fieldset>` disables all nested controls while
the block is hidden.

```blade
<hw:form conditional-fields :action="route('checkout.store')" method="post">
    <fieldset>
        <legend>Billing address</legend>
        <hw:input name="billing_address" />
        <hw:input name="billing_city" />
        <hw:input name="billing_zip" />
    </fieldset>

    <label>
        <input type="checkbox" name="ship_different" value="1" @checked(old('ship_different')) />
        Ship to a different address
    </label>

    <hw:conditional-field when="ship_different=:checked">
        <legend>Shipping address</legend>
        <hw:input name="shipping_address" />
        <hw:input name="shipping_city" />
        <hw:input name="shipping_zip" />
    </hw:conditional-field>

    <hw:button type="submit">Continue to payment</hw:button>
</hw:form>
```

## Show fields for selected plans

Radio groups and selects use their selected value. List alternatives with `|`:

```blade
<hw:form conditional-fields :action="route('subscriptions.store')" method="post">
    <fieldset>
        <legend>Plan</legend>

        @foreach (['starter' => 'Starter', 'pro' => 'Pro', 'enterprise' => 'Enterprise'] as $value => $label)
            <label>
                <input type="radio" name="plan" value="{{ $value }}" @checked(old('plan', 'starter') === $value) />
                {{ $label }}
            </label>
        @endforeach
    </fieldset>

    <hw:conditional-field when="plan=pro|enterprise">
        <hw:field name="team_size" label="How many seats?">
            <hw:input type="number" min="1" max="500" />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field when="plan=enterprise">
        <legend>Enterprise requirements</legend>
        <hw:input name="sla_contact" type="email" />
        <hw:input name="annual_volume" type="number" />
    </hw:conditional-field>
</hw:form>
```

## Branch on score ranges

HTML form values are strings. Use string values in rules, including numeric-looking values:

```blade
<hw:form conditional-fields :action="route('surveys.store')" method="post">
    <fieldset>
        <legend>How likely are you to recommend us?</legend>

        @foreach (range(0, 10) as $score)
            <label>
                <input
                    type="radio"
                    name="score"
                    value="{{ $score }}"
                    @checked((string) old('score') === (string) $score)
                />
                {{ $score }}
            </label>
        @endforeach
    </fieldset>

    <hw:conditional-field when="score=0|1|2|3|4|5|6">
        <hw:field name="reason_low" label="What is the main reason for that score?">
            <hw:textarea rows="3" />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field when="score=9|10">
        <hw:field name="reason_high" label="What did we do well?">
            <hw:textarea rows="3" />
        </hw:field>
    </hw:conditional-field>
</hw:form>
```

## Match a checkbox group

For `name="interests[]"`, a rule matches when any checked value equals one of its alternatives:

```blade
<hw:form conditional-fields :action="route('preferences.store')" method="post">
    <fieldset>
        <legend>I'm interested in</legend>

        @foreach (['news' => 'Product news', 'tips' => 'Tips', 'events' => 'Events'] as $value => $label)
            <label>
                <input
                    type="checkbox"
                    name="interests[]"
                    value="{{ $value }}"
                    @checked(in_array($value, old('interests', []), true))
                />
                {{ $label }}
            </label>
        @endforeach
    </fieldset>

    <hw:conditional-field when="interests=news|tips|events">
        <hw:field name="cadence" label="How often?">
            <hw:select :options="['weekly' => 'Weekly', 'monthly' => 'Monthly']" />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field when="interests=events">
        <label>
            <input type="checkbox" name="webinar_reminders" value="1" />
            Send a reminder before each event
        </label>
    </hw:conditional-field>
</hw:form>
```

## Preserve visibility in edit forms

Pass an Eloquent model, array, or object to the form's `state` prop. Every nested conditional field inherits that state
and resolves each trigger with `old($field, data_get($state, $field))`.

```blade
<hw:form conditional-fields :state="$message" :action="route('messages.update', $message)" method="patch">
    <hw:field name="reason" label="Reason">
        <hw:select :options="$reasons" :selected="$message->reason" />
    </hw:field>

    <hw:conditional-field when="reason=other">
        <hw:field name="other_reason" label="Tell us more">
            <hw:input :value="$message->other_reason" />
        </hw:field>
    </hw:conditional-field>

    <hw:button type="submit">Update message</hw:button>
</hw:form>
```

Validation input from `old()` takes precedence over `state`, so a failed submission restores both the trigger value and
the dependent block's first-render visibility. When trigger names do not match model keys, pass an associative state
array keyed by the field names used in `when`.

## Combine conditions

Separate conditions with spaces to require all of them:

```blade
<hw:conditional-field when="authorized=no needs_visa=yes">
    <hw:select name="sponsorship_country" :options="$countries" />
</hw:conditional-field>
```

Use the array form when it reads more clearly:

```blade
<hw:conditional-field :when="['plan' => ['pro', 'enterprise'], 'annual' => ':checked']">...</hw:conditional-field>
```

## See also

- [`<hw:conditional-field>` component](../components/conditional-field.md) - rule grammar, state, and wrapper options.
- [Conditional fields controller](../controllers/conditional-fields.md) - lower-level behavior and limitations.

<?php

namespace Emaia\LaravelHotwire\Support;

/**
 * Label wiring for owners that group several controls.
 *
 * A radio, checkbox or toggle set has no single labelable control, so `<label for>` would
 * point at an id no control carries. These owners name themselves with aria-labelledby
 * instead, which means the label needs a predictable id and the owner needs to find it in
 * its already-rendered slot.
 */
final class FieldLabel
{
    /** Derive the id a set label carries, from the owner id base or its name. */
    public static function idFor(?string $id, ?string $name): ?string
    {
        $base = $id ?: ($name !== null && $name !== '' ? FieldKey::toId($name) : null);

        return $base === null || $base === '' ? null : $base.'-label';
    }

    /** Find the id of the first field label in rendered slot HTML, if it carries one. */
    public static function findIn(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return null;
        }

        return preg_match('/<label[^>]*data-slot="field-label"[^>]*\bid="([^"]+)"/s', $html, $matches) === 1
            ? html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : null;
    }
}

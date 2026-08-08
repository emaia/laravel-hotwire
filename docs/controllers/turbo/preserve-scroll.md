# Turbo Preserve Scroll

Preserves the window scroll position around Turbo Frame renders.

Use it on frames whose content is replaced while the focused element is inside the frame, such as paginated result lists. The controller blurs the focused frame child before Turbo renders and restores the previous scroll position, clamped to the new document height.

```blade
<hw:frame id="results" preserve-scroll>
    ...
</hw:frame>
```

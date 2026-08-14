<?php

namespace Emaia\LaravelHotwire\Registry;

/**
 * The browse facet every catalog entry carries, shared by components and controllers.
 *
 * A component and the controller powering it belong to the same case — `hotwire:docs`
 * folds the category into its search string, so a split family becomes unfindable.
 */
enum Category: string
{
    case Display = 'display';
    case Feedback = 'feedback';
    case Forms = 'forms';
    case Navigation = 'navigation';
    case Overlay = 'overlay';
    case Turbo = 'turbo';
    case Utility = 'utility';
    case Dev = 'dev';
}

import { Controller } from "@hotwired/stimulus";
import { dispatchOptimistic } from "./_dispatch";

// Escape-hatch controller: exposes dispatch() as a Stimulus action.
//
// Use when neither optimistic--form nor optimistic--link fit your trigger.
// Wire any event to optimistic--dispatch#dispatch:
//
//   <div data-controller="optimistic--dispatch"
//        data-action="my-event->optimistic--dispatch#dispatch">
//       <template data-optimistic-stream ...>…</template>
//   </div>
export default class extends Controller {
    dispatch() {
        dispatchOptimistic(this.element);
    }
}

const result = await Bun.build({
    entrypoints: ["tests/Browser/fixtures/widget_snapshots.js"],
    target: "browser",
    format: "iife",
    loader: { ".css": "text", ".png": "dataurl" },
    plugins: [{
        name: "existing-turbo-runtime",
        setup(build) {
            // Overlay hosts dynamically import Turbo; use the test's single runtime.
            build.onResolve({ filter: /^@hotwired\/turbo$/ }, () => ({ path: "turbo", namespace: "test-runtime" }));
            build.onLoad({ filter: /.*/, namespace: "test-runtime" }, () => ({ contents: "export const StreamActions = window.Turbo.StreamActions;", loader: "js" }));
        },
    }],
});
if (!result.success) throw new AggregateError(result.logs);
process.stdout.write(await result.outputs[0].text());

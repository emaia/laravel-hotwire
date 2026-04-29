import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
    testDir: "./tests/Browser",
    testMatch: "**/*.pw.js",
    fullyParallel: true,
    reporter: "list",
    use: {
        browserName: "chromium",
        trace: "on-first-retry",
        ...devices["Desktop Chrome"],
    },
});

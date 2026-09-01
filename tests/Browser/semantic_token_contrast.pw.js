import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

const pairs = {
    background: ["--foreground", "--background"],
    card: ["--card-foreground", "--card"],
    popover: ["--popover-foreground", "--popover"],
    primary: ["--primary-foreground", "--primary"],
    secondary: ["--secondary-foreground", "--secondary"],
    muted: ["--muted-foreground", "--muted"],
    accent: ["--accent-foreground", "--accent"],
    destructive: ["--destructive-foreground", "--destructive"],
    sidebar: ["--sidebar-foreground", "--sidebar"],
    "sidebar-primary": ["--sidebar-primary-foreground", "--sidebar-primary"],
    "sidebar-accent": ["--sidebar-accent-foreground", "--sidebar-accent"],
};

test("semantic token pairs retain normal-text contrast in every package scope", async ({ page }) => {
    const tokens = await readFile("resources/css/tokens.css", "utf8");

    await page.setContent(`
        <style>${tokens}</style>
        <main id="default">
            <section id="light" data-theme="light">
                <div id="dark-inside-light" data-theme="dark"></div>
            </section>
            <section id="dark" data-theme="dark">
                <div id="light-inside-dark" data-theme="light"></div>
            </section>
        </main>
    `);

    const results = await page.evaluate(({ pairs }) => {
        const canvas = document.createElement("canvas");
        canvas.width = 1;
        canvas.height = 1;
        const context = canvas.getContext("2d", { colorSpace: "srgb" });

        const rgba = (color) => {
            context.clearRect(0, 0, 1, 1);
            context.fillStyle = color;
            context.fillRect(0, 0, 1, 1);

            return [...context.getImageData(0, 0, 1, 1).data];
        };
        const luminance = (color) => rgba(color)
            .slice(0, 3)
            .map((channel) => channel / 255)
            .map((channel) => channel <= 0.04045
                ? channel / 12.92
                : ((channel + 0.055) / 1.055) ** 2.4)
            .reduce((sum, channel, index) => sum + channel * [0.2126, 0.7152, 0.0722][index], 0);
        const scopes = {
            default: document.querySelector("#default"),
            light: document.querySelector("#light"),
            dark: document.querySelector("#dark"),
            "dark inside light": document.querySelector("#dark-inside-light"),
            "light inside dark": document.querySelector("#light-inside-dark"),
        };

        return Object.entries(scopes).flatMap(([scope, element]) => Object.entries(pairs).map(([pair, tokens]) => {
            const probe = document.createElement("span");
            const scopeStyles = getComputedStyle(element);
            const missing = tokens.filter((token) => scopeStyles.getPropertyValue(token).trim() === "");
            probe.style.color = `var(${tokens[0]}, rgb(1 2 3))`;
            probe.style.backgroundColor = `var(${tokens[1]}, rgb(1 2 3))`;
            element.append(probe);

            const styles = getComputedStyle(probe);
            const foreground = luminance(styles.color);
            const background = luminance(styles.backgroundColor);
            // A translucent channel is measured against a cleared canvas, so a fully transparent
            // foreground would read as black and score against the surface it never covers.
            const alpha = [styles.color, styles.backgroundColor].map((color) => rgba(color)[3]);
            probe.remove();

            return {
                scope,
                pair,
                missing,
                alpha,
                ratio: (Math.max(foreground, background) + 0.05)
                    / (Math.min(foreground, background) + 0.05),
            };
        }));
    }, { pairs });

    for (const result of results) {
        expect(result.missing, `${result.scope}: ${result.pair} has missing tokens`).toEqual([]);
        expect(
            result.alpha,
            `${result.scope}: ${result.pair} is not opaque, so its measured ratio is not what renders`,
        ).toEqual([255, 255]);
        expect(
            result.ratio,
            `${result.scope}: ${result.pair} has ${result.ratio.toFixed(2)}:1 contrast`,
        ).toBeGreaterThanOrEqual(4.5);
    }
});

import { readFile } from "node:fs/promises";

let snapshotRuntime;

/** Supply the shared snapshot dependency to legacy plain-script browser fixtures. */
export async function readControllerSource(path, encoding) {
    const source = await readFile(path, encoding);
    if (!source.includes('_snapshot.js"')) return source;
    snapshotRuntime ??= readFile("resources/js/controllers/_snapshot.js", "utf8").then((runtime) => {
        const names = [...runtime.matchAll(/export function (\w+)/g)].map((match) => match[1]);
        return `(() => { if (window.hotwireSnapshotTestRuntime) return;
            ${runtime.replace(/^export /gm, "")}
            Object.assign(window, { ${names.join(", ")} });
            window.hotwireSnapshotTestRuntime = true;
        })();`;
    });
    return source.replace(/^import .* from "\.{1,2}\/_snapshot\.js";\s*$/gm, await snapshotRuntime);
}

function loadCommonLayout() {
    const parser = new DOMParser();
    const body = document.body;
    const loaderScript = document.currentScript || [...document.scripts].find(script => script.src.includes("header-footer.js"));
    const layoutBase = loaderScript ? new URL("../", loaderScript.src) : new URL("./", document.baseURI);

    if (body) {
        body.style.visibility = "hidden";
    }

    function loadFragment(file, selectors, mountId, replaceSelectors, appendWhenMissing) {
        return fetch(new URL(file, layoutBase))
            .then(response => {
                if (!response.ok) {
                    throw new Error(file + " not found: " + response.status);
                }
                return response.text();
            })
            .then(data => {
                const fragmentDocument = parser.parseFromString(data, "text/html");
                const fragments = selectors
                    .map(selector => fragmentDocument.querySelector(selector))
                    .filter(Boolean);
                const mount = document.getElementById(mountId);

                if (!fragments.length) {
                    throw new Error(selectors.join(", ") + " missing from " + file);
                }

                const nodes = fragments.map(fragment => fragment.cloneNode(true));
                if (mount) {
                    mount.replaceChildren(...nodes);
                    return;
                }

                const existing = [...document.querySelectorAll(replaceSelectors)];
                if (existing.length) {
                    existing[0].before(...nodes);
                    existing.forEach(element => element.remove());
                } else if (appendWhenMissing) {
                    document.body.append(...nodes);
                }
            });
    }

    return Promise.all([
        loadFragment("header.html", [".topbar", "header.main-header"], "header", ".topbar, header.main-header", true),
        loadFragment("footer.html", ["footer.main-footer"], "footer", "footer.main-footer", true)
    ]).catch(error => console.error(error)).finally(() => {
        if (body) {
            body.style.visibility = "visible";
        }
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", loadCommonLayout, { once: true });
} else {
    loadCommonLayout();
}
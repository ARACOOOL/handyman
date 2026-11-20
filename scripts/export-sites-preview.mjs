import { cp, mkdir, readFile, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import path from "node:path";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const destination = path.join(root, "sites/lion-home-services-preview/dist");
const response = await fetch("http://localhost:8088/");
if (!response.ok) throw new Error(`Local PHP site returned ${response.status}`);
let html = await response.text();
html = html
  .replace(/\s*<input type="hidden" name="csrf"[^>]+>/, "")
  .replace('method="post" action="/request.php"', 'aria-describedby="preview-notice"')
  .replace('<p class="form-intro">Your project starts here.</p>', '<p class="form-intro">Your project starts here.</p><p id="preview-notice" class="preview-notice">Design preview only. This form does not send or save requests.</p><fieldset class="preview-fields" disabled>')
  .replace('<button class="button button-dark form-submit" type="submit"><span>Request a quote</span>', '<button class="button button-dark form-submit" type="button" disabled><span>Preview only</span>')
  .replace('No commitment. Just a conversation about your home.', 'Request submissions are disabled in this design preview.')
  .replace(/(<p class="form-footnote">[^<]+<\/p>)/, '$1</fieldset>')
  .replace('Your project request has been saved. Thank you for telling us what your home needs.', 'This is a design preview. No request has been sent or saved.');
if (html.includes('name="csrf"') || html.includes('/request.php') || html.includes('<?php')) throw new Error("Preview still contains PHP request state");
await mkdir(destination, { recursive: true });
await cp(path.join(root, "public/assets"), path.join(destination, "assets"), { recursive: true });
await cp(path.join(root, "public/favicon.svg"), path.join(destination, "favicon.svg"));
await writeFile(path.join(destination, "index.html"), html);
const javascript = await readFile(path.join(root, "public/assets/site.js"), "utf8");
const marker = '  form.addEventListener("submit", async';
const split = javascript.indexOf(marker);
if (split < 0) throw new Error("Could not locate PHP form handler");
await writeFile(path.join(destination, "assets/site.js"), javascript.slice(0, split) + '  form.addEventListener("submit", event => event.preventDefault());\n})();\n');
const css = await readFile(path.join(destination, "assets/site.css"), "utf8");
await writeFile(path.join(destination, "assets/site.css"), css + '\n.preview-notice{font-size:12px;line-height:1.7;color:var(--text);padding:12px 14px;border:1px solid var(--line);border-radius:8px;margin-bottom:20px;background:var(--panel)}.preview-fields{border:0;margin:0;padding:0;min-width:0}.preview-fields .form-submit:disabled{cursor:default;opacity:.8}.preview-fields input,.preview-fields textarea,.preview-fields select{opacity:1;-webkit-text-fill-color:var(--muted)}\n');
console.log(`Static design preview exported to ${destination}`);

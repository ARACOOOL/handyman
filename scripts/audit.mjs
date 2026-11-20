import lighthouse from "lighthouse";
import { chromium } from "@playwright/test";
import { mkdir, writeFile } from "node:fs/promises";
import { spawn } from "node:child_process";
const chrome = spawn(
  chromium.executablePath(),
  [
    "--headless",
    "--no-sandbox",
    "--remote-debugging-port=9223",
    "--user-data-dir=/tmp/lion-lighthouse-profile",
  ],
  { stdio: "ignore" },
);
try {
  for (let attempt = 0; attempt < 40; attempt++) {
    try {
      if ((await fetch("http://localhost:9223/json/version")).ok) break;
    } catch {}
    await new Promise((resolve) => setTimeout(resolve, 100));
  }
  await mkdir("reports", { recursive: true });
  const result = await lighthouse("http://localhost:8088", {
    port: 9223,
    output: "html",
    onlyCategories: ["performance", "accessibility", "best-practices", "seo"],
  });
  await writeFile("reports/lighthouse.html", result.report);
  const scores = Object.fromEntries(
    Object.entries(result.lhr.categories).map(([key, value]) => [
      key,
      Math.round(value.score * 100),
    ]),
  );
  console.log(JSON.stringify(scores, null, 2));
  for (const audit of Object.values(result.lhr.audits)) {
    if (
      audit.score !== null &&
      audit.score < 1 &&
      audit.scoreDisplayMode !== "notApplicable"
    )
      console.log(
        audit.id,
        audit.title,
        audit.displayValue || "",
        JSON.stringify(audit.details?.items || []).slice(0, 2000),
      );
  }
  await writeFile(
    "reports/lighthouse.json",
    JSON.stringify(result.lhr, null, 2),
  );
} finally {
  chrome.kill();
}
